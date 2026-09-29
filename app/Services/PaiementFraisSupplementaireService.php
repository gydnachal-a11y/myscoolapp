<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\Eleve;
use App\Models\FraisSupplementaire;
use App\Models\Inscription;
use App\Models\PaiementFraisSupplementaire;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaiementFraisSupplementaireService
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const CACHE_TTL  = 3600;
    private const TOLERANCE  = 0.01;

    // ============================================================
    // CRÉATION — paiement simple
    // ============================================================

    /**
     * Enregistre le paiement d'un frais supplémentaire pour un élève.
     *
     * @throws ValidationException
     */
    public function enregistrerPaiement(
        Eleve $eleve,
        FraisSupplementaire $frais,
        ?string $commentaire = null,
        ?float $montantPayeUsd = null
    ): PaiementFraisSupplementaire {
        $this->validerReglesMetier($eleve, $frais, null, null);

        $montantUsd = $this->normaliserMontant($montantPayeUsd, $frais);
        $montantFc  = $this->convertirEnFc($montantUsd);

        $paiement = DB::transaction(function () use ($eleve, $frais, $montantUsd, $montantFc, $commentaire) {
            return PaiementFraisSupplementaire::create([
                'eleve_id'                => $eleve->id,
                'frais_supplementaire_id' => $frais->id,
                'montant_paye_usd'        => $montantUsd,
                'montant_paye_fc'         => $montantFc,
                'date_paiement'           => now()->toDateString(),
                'commentaire'             => $commentaire,
            ]);
        });

        $this->logPaiement('créé', $paiement);

        return $paiement;
    }

    // ============================================================
    // CRÉATION — paiement groupé
    // ============================================================

    /**
     * Enregistre un paiement groupé pour plusieurs élèves.
     * Le montant total est réparti **équitablement** (dernier élève = ajustement).
     *
     * @param  array<int, Eleve>  $eleves
     * @return Collection<int, PaiementFraisSupplementaire>
     *
     * @throws ValidationException
     */
    public function enregistrerPaiementGroupe(
        array $eleves,
        FraisSupplementaire $frais,
        float $montantTotalUsd,
        ?string $commentaire = null
    ): Collection {
        if (empty($eleves)) {
            throw ValidationException::withMessages([
                'eleves' => 'Aucun élève sélectionné.',
            ]);
        }

        if ($montantTotalUsd <= 0) {
            throw ValidationException::withMessages([
                'montant' => 'Le montant total doit être supérieur à 0.',
            ]);
        }

        $nbEleves = count($eleves);

        // Vérifications préalables (avant transaction)
        foreach ($eleves as $eleve) {
            $this->validerReglesMetier($eleve, $frais, null, null);
        }

        $parts = $this->repartirMontant($montantTotalUsd, $nbEleves);

        $paiements = DB::transaction(function () use ($eleves, $frais, $parts, $commentaire): Collection {
            $results = collect();

            foreach ($eleves as $index => $eleve) {
                $montantUsd = $parts[$index];
                $montantFc  = $this->convertirEnFc($montantUsd);

                $results->push(PaiementFraisSupplementaire::create([
                    'eleve_id'                => $eleve->id,
                    'frais_supplementaire_id' => $frais->id,
                    'montant_paye_usd'        => $montantUsd,
                    'montant_paye_fc'         => $montantFc,
                    'date_paiement'           => now()->toDateString(),
                    'commentaire'             => $commentaire,
                ]));
            }

            return $results;
        });

        Log::info('Paiement groupé créé', [
            'frais_id'  => $frais->id,
            'nb_eleves' => $nbEleves,
            'total_usd' => $montantTotalUsd,
        ]);

        return $paiements;
    }

    // ============================================================
    // MISE À JOUR
    // ============================================================

    /**
     * Met à jour un paiement existant.
     *
     * @throws ValidationException
     */
    public function mettreAJourPaiement(
        PaiementFraisSupplementaire $paiement,
        Eleve $eleve,
        FraisSupplementaire $frais,
        ?string $commentaire = null,
        ?float $montantPayeUsd = null
    ): PaiementFraisSupplementaire {
        $this->validerReglesMetier($eleve, $frais, null, $paiement->id);

        $montantUsd = $this->normaliserMontant($montantPayeUsd, $frais);
        $montantFc  = $this->convertirEnFc($montantUsd);

        $paiement = DB::transaction(function () use ($paiement, $eleve, $frais, $montantUsd, $montantFc, $commentaire) {
            $paiement->update([
                'eleve_id'                => $eleve->id,
                'frais_supplementaire_id' => $frais->id,
                'montant_paye_usd'        => $montantUsd,
                'montant_paye_fc'         => $montantFc,
                'commentaire'             => $commentaire,
            ]);

            return $paiement->fresh();
        });

        $this->logPaiement('mis à jour', $paiement);

        return $paiement;
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    /**
     * Supprime un paiement.
     */
    public function supprimerPaiement(PaiementFraisSupplementaire $paiement): void
    {
        DB::transaction(fn () => $paiement->delete());

        Log::info('Paiement frais supplémentaire supprimé', [
            'paiement_id' => $paiement->id,
            'eleve_id'    => $paiement->eleve_id,
            'frais_id'    => $paiement->frais_supplementaire_id,
        ]);
    }

    // ============================================================
    // VALIDATION CENTRALISÉE
    // ============================================================

    /**
     * Valide les règles métier communes (create + update).
     *
     * @throws ValidationException
     */
    private function validerReglesMetier(
        Eleve $eleve,
        FraisSupplementaire $frais,
        ?int $ignoreId = null,
        ?int $ignorePaiementId = null
    ): void {
        $this->verifierFraisOuvert($frais);
        $this->verifierPeriodeValide($frais);
        $this->verifierEleveConcerne($eleve, $frais);
        $this->verifierPasDeDoublePaiement($eleve, $frais, $ignorePaiementId);
    }

    /**
     * @throws ValidationException
     */
    private function verifierFraisOuvert(FraisSupplementaire $frais): void
    {
        if (!$frais->est_ouvert) {
            throw ValidationException::withMessages([
                'frais' => "Ce frais n'est pas ouvert au paiement.",
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifierPeriodeValide(FraisSupplementaire $frais): void
    {
        if (!$frais->estEnCours() && !$frais->estAVenir()) {
            throw ValidationException::withMessages([
                'frais' => 'La période de paiement de ce frais est expirée.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifierEleveConcerne(Eleve $eleve, FraisSupplementaire $frais): void
    {
        $salleId = $this->getSalleActiveEleve($eleve);

        if (!$salleId || !$frais->concerneSalle($salleId)) {
            throw ValidationException::withMessages([
                'eleve' => "L'élève {$eleve->nom_complet} n'est pas concerné par ce frais.",
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifierPasDeDoublePaiement(
        Eleve $eleve,
        FraisSupplementaire $frais,
        ?int $ignorePaiementId = null
    ): void {
        $exists = PaiementFraisSupplementaire::query()
            ->where('eleve_id', $eleve->id)
            ->where('frais_supplementaire_id', $frais->id)
            ->when($ignorePaiementId, fn ($q) => $q->where('id', '!=', $ignorePaiementId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'paiement' => "L'élève {$eleve->nom_complet} a déjà payé ce frais.",
            ]);
        }
    }

    // ============================================================
    // HELPERS PRIVÉS
    // ============================================================

    /**
     * Salle active de l'élève (année en cours).
     */
    private function getSalleActiveEleve(Eleve $eleve): ?int
    {
        $anneeActiveId = $this->getAnneeActiveId();

        if (!$anneeActiveId) {
            return null;
        }

        return Inscription::query()
            ->where('eleve_id', $eleve->id)
            ->where('annee_scolaire_id', $anneeActiveId)
            ->value('salle_classe_id');
    }

    /**
     * Normalise + valide le montant.
     *
     * @throws ValidationException
     */
    private function normaliserMontant(?float $montant, FraisSupplementaire $frais): float
    {
        $montantUsd = (float) ($montant ?? $frais->montant);

        if (abs($montantUsd - (float) $frais->montant) > self::TOLERANCE) {
            throw ValidationException::withMessages([
                'montant' => 'Le montant payé doit correspondre au montant du frais.',
            ]);
        }

        return $montantUsd;
    }

    /**
     * Conversion USD → FC (arrondi à 2 décimales).
     */
    private function convertirEnFc(float $montantUsd): float
    {
        return round($montantUsd * $this->getTauxChange(), 2);
    }

    /**
     * Répartit un montant total sur N élèves (dernier ajusté pour la somme exacte).
     *
     * @return array<int, float>
     */
    private function repartirMontant(float $montantTotal, int $nbEleves): array
    {
        if ($nbEleves <= 0) {
            return [];
        }

        $part  = round($montantTotal / $nbEleves, 2);
        $somme = $part * $nbEleves;
        $diff  = round($montantTotal - $somme, 2);

        $parts = array_fill(0, $nbEleves, $part);
        $parts[$nbEleves - 1] = round($part + $diff, 2);

        return $parts;
    }

    /**
     * Année scolaire active (cache 1h).
     */
    private function getAnneeActiveId(): ?int
    {
        return Cache::remember('annee_active_id', self::CACHE_TTL, function (): ?int {
            return AnneeScolaire::where('cloturee', false)
                ->latest('date_debut')
                ->value('id');
        });
    }

    /**
     * Taux de change USD → CDF.
     * ✅ Délègue au modèle Devise (lui-même caché) — plus de double cache.
     */
    private function getTauxChange(): float
    {
        try {
            return Devise::tauxUsdVersCdf();
        } catch (Throwable $e) {
            Log::warning('Taux de change indisponible', [
                'error' => $e->getMessage(),
            ]);

            return Devise::TAUX_PAR_DEFAUT;
        }
    }

    /**
     * Log d'audit standardisé.
     */
    private function logPaiement(string $action, PaiementFraisSupplementaire $paiement): void
    {
        Log::info("Paiement frais supplémentaire {$action}", [
            'paiement_id' => $paiement->id,
            'eleve_id'    => $paiement->eleve_id,
            'frais_id'    => $paiement->frais_supplementaire_id,
            'montant_usd' => $paiement->montant_paye_usd,
            'montant_fc'  => $paiement->montant_paye_fc,
        ]);
    }
}