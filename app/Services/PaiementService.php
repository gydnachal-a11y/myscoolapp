<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AnneeScolaire;
use App\Models\Devise;
use App\Models\Inscription;
use App\Models\MoisScolaire;
use App\Models\Paiement;
use App\Models\SalleDeClasse;
use App\Models\Section;
use App\Models\SessionPaiement;
use App\Models\TrancheScolaire;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaiementService
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const MODE_MENSUEL = Paiement::MODE_MENSUEL;
    private const MODE_TRANCHE = Paiement::MODE_TRANCHE;

    private const STATUT_PAYE    = Paiement::STATUT_PAYE;
    private const STATUT_PARTIEL = Paiement::STATUT_PARTIEL;
    private const STATUT_IMPAYE  = Paiement::STATUT_IMPAYE;
    private const STATUT_SURPAYE = Paiement::STATUT_SURPAYE;

    /** ✅ Clé de cache uniformisée avec le reste de l'app. */
    private const CACHE_KEY_TAUX     = 'taux_change';
    private const CACHE_TTL_TAUX     = 3600;
    private const TAUX_CHANGE_DEFAUT = 2800.0;

    /** Tolérance de comparaison entre deux montants (USD). */
    private const EPSILON = 0.001;

    /** Précision monétaire (2 décimales). */
    private const MONTANT_PRECISION = 2;

    // ============================================================
    // CRÉATION — PAIEMENT SIMPLE
    // ============================================================

    /**
     * Crée un paiement simple.
     *
     * @throws ValidationException
     */
    public function creerPaiement(array $data, AnneeScolaire $anneeActive): Paiement
    {
        $this->verifierPaiementPossible($anneeActive);

        $eleveId = (int) $data['eleve_id'];
        $salleId = (int) $data['salle_classe_id'];

        $this->verifierInscription($anneeActive, $eleveId, $salleId);

        $salle       = SalleDeClasse::findOrFail($salleId);
        $periode     = (int) $data['periode'];
        $typePeriode = (string) ($data['type_periode'] ?? $salle->mode_paiement);

        if ($typePeriode !== $salle->mode_paiement) {
            throw ValidationException::withMessages([
                'type_periode' => "Le type de période ne correspond pas au mode de paiement de la salle ({$salle->mode_paiement}).",
            ]);
        }

        $this->verifierDoublon($eleveId, $anneeActive->id, $salleId, $periode);

        $session  = $this->trouverSessionPaiement($salle, $typePeriode, $periode);
        $montants = $this->calculerMontants($data);

        return DB::transaction(fn (): Paiement => Paiement::create(
            $this->buildPaiementData($data, $anneeActive, $salle, $montants, $session, $typePeriode)
        ));
    }

    // ============================================================
    // CRÉATION — PAIEMENTS GROUPÉS
    // ============================================================

    /**
     * Crée plusieurs paiements en une seule transaction.
     *
     * @return array<int, Paiement>
     * @throws ValidationException
     */
    public function creerPaiementsMultiples(array $data, AnneeScolaire $anneeActive): array
    {
        $this->verifierPaiementPossible($anneeActive);

        $salleId        = (int) $data['salle_classe_id'];
        $typePeriode    = (string) $data['type_periode'];
        $periode        = (int) $data['periode'];
        $eleveIds       = array_values(array_unique(array_map('intval', $data['eleve_ids'])));
        $montantPayeUsd = (float) $data['montant_paye_usd'];
        $commentaire    = $data['commentaire'] ?? null;

        $salle = SalleDeClasse::findOrFail($salleId);

        if ($typePeriode !== $salle->mode_paiement) {
            throw ValidationException::withMessages([
                'type_periode' => 'Le type de période ne correspond pas au mode de paiement de la salle.',
            ]);
        }

        /* ---------- Vérification des inscriptions (1 requête) ---------- */
        $inscrits = Inscription::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->where('salle_classe_id', $salleId)
            ->whereIn('eleve_id', $eleveIds)
            ->pluck('eleve_id')
            ->all();

        if (count($inscrits) !== count($eleveIds)) {
            throw ValidationException::withMessages([
                'eleve_ids' => "Un ou plusieurs élèves ne sont pas inscrits dans cette salle pour l'année active.",
            ]);
        }

        /* ---------- Vérification des doublons (1 requête) ---------- */
        $doublons = Paiement::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->where('salle_classe_id', $salleId)
            ->where('periode', $periode)
            ->whereIn('eleve_id', $eleveIds)
            ->pluck('eleve_id')
            ->all();

        if (! empty($doublons)) {
            throw ValidationException::withMessages([
                'eleve_ids' => sprintf(
                    'Un paiement existe déjà pour ces élèves pour cette période : %s.',
                    implode(', ', $doublons)
                ),
            ]);
        }

        $session = $this->trouverSessionPaiement($salle, $typePeriode, $periode);

        /* ---------- Calcul des montants ---------- */
        $montantAttenduParEleve = $this->getMontantAttenduParEleve($salle, $typePeriode);
        $nbEleves               = count($eleveIds);
        $totalAttendu           = round($montantAttenduParEleve * $nbEleves, self::MONTANT_PRECISION);

        if ($nbEleves > 1) {
            if (abs($montantPayeUsd - $totalAttendu) > self::EPSILON) {
                throw ValidationException::withMessages([
                    'montant_paye_usd' => sprintf(
                        'Pour un paiement groupé, le montant payé doit être exactement égal au total attendu (%s USD).',
                        number_format($totalAttendu, 2, ',', ' ')
                    ),
                ]);
            }
            $montantPayeParEleve = $montantAttenduParEleve;
        } else {
            if ($montantPayeUsd <= 0) {
                throw ValidationException::withMessages([
                    'montant_paye_usd' => 'Le montant payé doit être supérieur à 0.',
                ]);
            }
            $montantPayeParEleve = $montantPayeUsd;
        }

        $taux = $this->getTauxChange();

        $dataCommune = $this->preparerLignePaiement(
            $anneeActive,
            $salle,
            $session,
            $typePeriode,
            $periode,
            $montantAttenduParEleve,
            $montantPayeParEleve,
            $taux,
            $commentaire
        );

        /* ---------- Insertion groupée en 1 transaction ---------- */
        return DB::transaction(function () use ($eleveIds, $dataCommune): array {
            $paiements = [];

            foreach ($eleveIds as $eleveId) {
                $paiements[] = Paiement::create(['eleve_id' => $eleveId] + $dataCommune);
            }

            return $paiements;
        });
    }

    // ============================================================
    // MISE À JOUR
    // ============================================================

    /**
     * @throws ValidationException
     */
    public function mettreAJourPaiement(
        Paiement $paiement,
        array $data,
        AnneeScolaire $anneeActive
    ): Paiement {
        $this->verifierPaiementPossible($anneeActive);

        $eleveId = (int) ($data['eleve_id']        ?? $paiement->eleve_id);
        $salleId = (int) ($data['salle_classe_id'] ?? $paiement->salle_classe_id);
        $periode = (int) ($data['periode']         ?? $paiement->periode);

        $this->verifierInscription($anneeActive, $eleveId, $salleId);
        $this->verifierDoublon($eleveId, $anneeActive->id, $salleId, $periode, $paiement->id);

        $salle       = SalleDeClasse::findOrFail($salleId);
        $typePeriode = (string) ($data['type_periode'] ?? $salle->mode_paiement);

        if ($typePeriode !== $salle->mode_paiement) {
            throw ValidationException::withMessages([
                'type_periode' => 'Le type de période ne correspond pas au mode de paiement de la salle.',
            ]);
        }

        $session  = $this->trouverSessionPaiement($salle, $typePeriode, $periode);
        $montants = $this->calculerMontants($data);

        $payload = $this->buildPaiementData(
            $data,
            $anneeActive,
            $salle,
            $montants,
            $session,
            $typePeriode,
            $paiement
        );

        DB::transaction(fn () => $paiement->update($payload));

        return $paiement->fresh();
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    /**
     * @throws ValidationException
     */
    public function supprimerPaiement(Paiement $paiement, AnneeScolaire $anneeActive): void
    {
        $this->verifierPaiementPossible($anneeActive);

        if ((int) $paiement->annee_scolaire_id !== (int) $anneeActive->id) {
            throw ValidationException::withMessages([
                'paiement' => 'Ce paiement appartient à une année scolaire différente.',
            ]);
        }

        DB::transaction(fn () => $paiement->delete());
    }

    // ============================================================
    // VÉRIFICATION DOUBLON PUBLIQUE
    // ============================================================

    public function paiementExiste(
        int $eleveId,
        int $anneeScolaireId,
        string $typePeriode,
        ?int $periodeId
    ): bool {
        return Paiement::query()
            ->where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeScolaireId)
            ->where('type_periode', $typePeriode)
            ->where('periode', $periodeId)
            ->exists();
    }

    // ============================================================
    // STATISTIQUES PAR SALLE
    // ============================================================

    /**
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    public function getStatistiquesParSalle(AnneeScolaire $anneeActive, ?int $salleId = null): array
    {
        $nbMois = MoisScolaire::where('annee_scolaire_id', $anneeActive->id)->count();

        $sallesQuery = SalleDeClasse::query()
            ->withCount([
                'inscriptions as nb_eleves' => fn ($q) => $q->where('annee_scolaire_id', $anneeActive->id),
            ]);

        if ($salleId) {
            $sallesQuery->where('id', $salleId);
        }

        $salles = $sallesQuery->get();

        if ($salleId && $salles->isEmpty()) {
            throw ValidationException::withMessages([
                'salle' => "La salle demandée n'existe pas.",
            ]);
        }

        /* Total payé par salle (1 requête) */
        $paiementsParSalle = Paiement::query()
            ->where('annee_scolaire_id', $anneeActive->id)
            ->when($salleId, fn ($q) => $q->where('salle_classe_id', $salleId))
            ->groupBy('salle_classe_id')
            ->select('salle_classe_id', DB::raw('SUM(montant_paye_usd) as total_paye'))
            ->pluck('total_paye', 'salle_classe_id')
            ->all();

        $stats = [];

        foreach ($salles as $salle) {
            $totalPaye    = (float) ($paiementsParSalle[$salle->id] ?? 0);
            $totalAttendu = $this->calculerTotalAttenduPourSalle($salle, $nbMois);

            $stats[] = [
                'salle_id'          => $salle->id,
                'salle_nom'         => $salle->nom,
                'nb_eleves'         => (int) $salle->nb_eleves,
                'total_attendu_usd' => $totalAttendu,
                'total_paye_usd'    => $totalPaye,
                'reste_a_payer'     => max(0, $totalAttendu - $totalPaye),
                'pourcentage_paye'  => $totalAttendu > 0
                    ? round(($totalPaye / $totalAttendu) * 100, 2)
                    : 0.0,
            ];
        }

        return $stats;
    }

    // ============================================================
    // DONNÉES POUR FORMULAIRES
    // ============================================================

    /**
     * Retourne toutes les données nécessaires aux formulaires create/edit.
     */
    public function getFormData(?AnneeScolaire $anneeActive, ?int $anneeId = null): array
    {
        $anneeId        = $anneeId ?? $anneeActive?->id;
        $paiementOuvert = (bool) ($anneeActive?->paiement_ouvert ?? false);

        /* ---------- Salles + inscriptions élève (1 requête) ---------- */
        $salles = SalleDeClasse::query()
            ->with([
                'section:id,nom,session_id',
                'section.session:id,nom',
                'inscriptions' => fn ($q) => $q->where('annee_scolaire_id', $anneeId)
                    ->with('eleve:id,nom,postnom,prenom'),
            ])
            ->whereHas('inscriptions', fn ($q) => $q->where('annee_scolaire_id', $anneeId))
            ->orderBy('nom')
            ->get();

        $sallesData     = [];
        $elevesParSalle = [];

        foreach ($salles as $salle) {
            $inscriptions = $salle->inscriptions;

            $elevesParSalle[$salle->id] = $inscriptions
                ->map(fn ($ins) => [
                    'id'          => $ins->eleve?->id,
                    'nom_complet' => trim(
                        ($ins->eleve?->nom ?? '') . ' ' .
                        ($ins->eleve?->postnom ?? '') . ' ' .
                        ($ins->eleve?->prenom ?? '')
                    ) ?: '—',
                ])
                ->filter(fn ($e) => $e['id'] !== null)
                ->values()
                ->all();

            $sallesData[] = [
                'id'                      => $salle->id,
                'nom'                     => $salle->nom,
                'section_id'              => $salle->section_id,
                'section'                 => $salle->section?->nom ?? '—',
                'session'                 => $salle->section?->session?->nom ?? '—',
                'nb_eleves'               => $inscriptions->count(),
                'frais_inscription'       => (float) $salle->frais_inscription,
                'frais_annuel'            => (float) $salle->frais_annuel,
                'mode_paiement'           => $salle->mode_paiement,
                'frais_scolarite_mensuel' => (float) ($salle->frais_scolarite_mensuel ?? 0),
                'nombre_tranches'         => (int) ($salle->nombre_tranches ?? 1),
                'frais_par_tranche'       => (float) ($salle->frais_par_tranche ?? 0),
                'paiement_ouvert'         => $paiementOuvert,
            ];
        }

        /* ---------- Sections (pour cascade) ---------- */
        $sections = Section::query()
            ->orderBy('nom')
            ->get(['id', 'nom'])
            ->map(fn ($s) => ['id' => $s->id, 'nom' => $s->nom])
            ->values()
            ->all();

        /* ---------- Mois ---------- */
        $mois = MoisScolaire::query()
            ->where('annee_scolaire_id', $anneeId)
            ->orderBy('mois')
            ->get(['id', 'mois', 'nom_mois'])
            ->map(fn ($m) => [
                'value' => $m->id,
                'label' => $m->nom_mois ?? $m->mois,
            ])
            ->values()
            ->all();

        /* ---------- Tranches ---------- */
        $tranches = TrancheScolaire::query()
            ->where('annee_scolaire_id', $anneeId)
            ->orderBy('tranche')
            ->get(['id', 'tranche'])
            ->map(fn ($t) => [
                'value' => $t->id,
                'label' => $t->tranche,
            ])
            ->values()
            ->all();

        return [
            'anneeActive'    => $anneeActive,
            'sallesData'     => $sallesData,
            'sections'       => $sections,
            'elevesParSalle' => $elevesParSalle,
            'mois'           => $mois,
            'tranches'       => $tranches,
            'tauxChange'     => $this->getTauxChangeSafe(),
        ];
    }

    /**
     * Alias public de `elevesParSalle` (réutilisable hors formulaire).
     */
    public function getElevesParSalle(AnneeScolaire $anneeActive): array
    {
        return $this->getFormData($anneeActive)['elevesParSalle'];
    }

    // ============================================================
    // ANNÉE ACTIVE
    // ============================================================

    public function getAnneeActive(): ?AnneeScolaire
    {
        return AnneeScolaire::where('cloturee', false)
            ->latest('date_debut')
            ->first();
    }

    // ============================================================
    // TAUX DE CHANGE
    // ============================================================

    public function getTauxChange(): float
    {
        return (float) Cache::remember(
            self::CACHE_KEY_TAUX,
            self::CACHE_TTL_TAUX,
            function (): float {
                try {
                    $source = Devise::where('code', 'USD')->first();
                    $cible  = Devise::where('code', 'CDF')->first();

                    if (! $source || ! $cible) {
                        Log::warning('Taux USD/CDF non configuré — fallback utilisé', [
                            'fallback' => self::TAUX_CHANGE_DEFAUT,
                        ]);

                        return self::TAUX_CHANGE_DEFAUT;
                    }

                    return (float) $source->tauxVers($cible);

                } catch (Throwable $e) {
                    Log::error('Erreur lecture taux de change', [
                        'error' => $e->getMessage(),
                    ]);

                    return self::TAUX_CHANGE_DEFAUT;
                }
            }
        );
    }

    public function getTauxChangeSafe(): float
    {
        return $this->getTauxChange();
    }

    public function refreshTauxChange(): float
    {
        Cache::forget(self::CACHE_KEY_TAUX);

        return $this->getTauxChange();
    }

    // ============================================================
    // MÉTHODES PRIVÉES — VÉRIFICATIONS
    // ============================================================

    /**
     * @throws ValidationException
     */
    private function verifierPaiementPossible(AnneeScolaire $anneeActive): void
    {
        if ($anneeActive->cloturee) {
            throw ValidationException::withMessages([
                'paiement' => "L'année scolaire est clôturée. Aucune opération de paiement n'est possible.",
            ]);
        }

        if (! $anneeActive->paiement_ouvert) {
            throw ValidationException::withMessages([
                'paiement' => 'La session de paiement est fermée.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifierInscription(AnneeScolaire $anneeActive, int $eleveId, int $salleId): void
    {
        $inscrit = Inscription::query()
            ->where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeActive->id)
            ->where('salle_classe_id', $salleId)
            ->exists();

        if (! $inscrit) {
            throw ValidationException::withMessages([
                'eleve_id' => "L'élève n'est pas inscrit dans cette salle pour l'année active.",
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function verifierDoublon(
        int $eleveId,
        int $anneeId,
        int $salleId,
        int $periode,
        ?int $excludeId = null
    ): void {
        $exists = Paiement::query()
            ->where('eleve_id', $eleveId)
            ->where('annee_scolaire_id', $anneeId)
            ->where('salle_classe_id', $salleId)
            ->where('periode', $periode)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'periode' => 'Un paiement existe déjà pour cet élève, cette salle et cette période.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function trouverSessionPaiement(
        SalleDeClasse $salle,
        string $typePeriode,
        int $periode
    ): SessionPaiement {
        $session = SessionPaiement::query()
            ->where('salle_classe_id', $salle->id)
            ->where('type_periode', $typePeriode)
            ->where('periode', $periode)
            ->first();

        if (! $session) {
            throw ValidationException::withMessages([
                'periode' => "Aucune session de paiement planifiée pour cette salle et cette période.",
            ]);
        }

        return $session;
    }

    // ============================================================
    // MÉTHODES PRIVÉES — CONSTRUCTION
    // ============================================================

    /**
     * Prépare la ligne de base pour un paiement groupé (sans eleve_id).
     *
     * @return array<string, mixed>
     */
    private function preparerLignePaiement(
        AnneeScolaire $anneeActive,
        SalleDeClasse $salle,
        SessionPaiement $session,
        string $typePeriode,
        int $periode,
        float $montantAttenduUsd,
        float $montantPayeUsd,
        float $taux,
        ?string $commentaire
    ): array {
        $attenduFc  = round($montantAttenduUsd * $taux, self::MONTANT_PRECISION);
        $payeFc     = round($montantPayeUsd * $taux, self::MONTANT_PRECISION);
        $restantUsd = round(max(0, $montantAttenduUsd - $montantPayeUsd), self::MONTANT_PRECISION);

        return [
            'annee_scolaire_id'   => $anneeActive->id,
            'salle_classe_id'     => $salle->id,
            'session_paiement_id' => $session->id,
            'type_periode'        => $typePeriode,
            'periode'             => $periode,
            'montant_attendu_usd' => $montantAttenduUsd,
            'montant_attendu_fc'  => $attenduFc,
            'montant_paye_usd'    => $montantPayeUsd,
            'montant_paye_fc'     => $payeFc,
            'montant_restant_usd' => $restantUsd,
            'montant_restant_fc'  => round(max(0, $attenduFc - $payeFc), self::MONTANT_PRECISION),
            'statut'              => $this->determinerStatut($montantAttenduUsd, $montantPayeUsd),
            'date_paiement'       => now(),
            'commentaire'         => $commentaire,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPaiementData(
        array $data,
        AnneeScolaire $anneeActive,
        SalleDeClasse $salle,
        array $montants,
        SessionPaiement $session,
        string $typePeriode,
        ?Paiement $paiement = null
    ): array {
        return [
            'eleve_id'            => $data['eleve_id'] ?? $paiement?->eleve_id,
            'annee_scolaire_id'   => $anneeActive->id,
            'salle_classe_id'     => $salle->id,
            'session_paiement_id' => $session->id,
            'type_periode'        => $typePeriode,
            'periode'             => $data['periode'] ?? $paiement?->periode,
            'montant_attendu_usd' => $montants['attendu_usd'],
            'montant_attendu_fc'  => $montants['attendu_fc'],
            'montant_paye_usd'    => $montants['paye_usd'],
            'montant_paye_fc'     => $montants['paye_fc'],
            'montant_restant_usd' => $montants['restant_usd'],
            'montant_restant_fc'  => $montants['restant_fc'],
            'statut'              => $montants['statut'],
            'date_paiement'       => $paiement?->date_paiement ?? now(),
            'commentaire'         => $data['commentaire'] ?? $paiement?->commentaire,
        ];
    }

    /**
     * Calcule les montants USD/FC pour un paiement.
     *
     * @return array{
     *     attendu_usd: float, attendu_fc: float,
     *     paye_usd: float, paye_fc: float,
     *     restant_usd: float, restant_fc: float,
     *     statut: string
     * }
     */
    private function calculerMontants(array $data): array
    {
        $taux = $this->getTauxChange();

        $attenduUsd = round((float) $data['montant_attendu_usd'], self::MONTANT_PRECISION);
        $payeUsd    = round((float) $data['montant_paye_usd'],    self::MONTANT_PRECISION);
        $restantUsd = round(max(0, $attenduUsd - $payeUsd),       self::MONTANT_PRECISION);

        return [
            'attendu_usd' => $attenduUsd,
            'attendu_fc'  => round($attenduUsd * $taux, self::MONTANT_PRECISION),
            'paye_usd'    => $payeUsd,
            'paye_fc'     => round($payeUsd * $taux,    self::MONTANT_PRECISION),
            'restant_usd' => $restantUsd,
            'restant_fc'  => round($restantUsd * $taux, self::MONTANT_PRECISION),
            'statut'      => $this->determinerStatut($attenduUsd, $payeUsd),
        ];
    }

    private function getMontantAttenduParEleve(SalleDeClasse $salle, string $typePeriode): float
    {
        return $typePeriode === self::MODE_MENSUEL
            ? (float) $salle->frais_scolarite_mensuel
            : (float) $salle->frais_par_tranche;
    }

    private function determinerStatut(float $attendu, float $paye): string
    {
        return match (true) {
            $paye > $attendu + self::EPSILON      => self::STATUT_SURPAYE,
            abs($paye - $attendu) <= self::EPSILON => self::STATUT_PAYE,
            $paye > 0                              => self::STATUT_PARTIEL,
            default                                => self::STATUT_IMPAYE,
        };
    }

    private function calculerTotalAttenduPourSalle(SalleDeClasse $salle, int $nbMois): float
    {
        $nbEleves = (int) ($salle->nb_eleves ?? 0);

        if ($nbEleves === 0) {
            return 0.0;
        }

        if ($salle->mode_paiement === self::MODE_MENSUEL) {
            return round(
                $nbEleves * (float) $salle->frais_scolarite_mensuel * $nbMois,
                self::MONTANT_PRECISION
            );
        }

        $nbTranches = (int) ($salle->nombre_tranches ?? 1);

        return round(
            $nbEleves * (float) $salle->frais_par_tranche * $nbTranches,
            self::MONTANT_PRECISION
        );
    }
}