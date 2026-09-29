<?php

namespace App\Services;

use App\Models\MoisScolaire;
use App\Models\PaiementSalaire;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class PaiementSalaireService
{
    // ============================================================
    // CONSTANTES — Délégation au modèle
    // ============================================================

    public const STATUT_PAYE     = PaiementSalaire::STATUT_PAYE;
    public const STATUT_SOUSPAYE = PaiementSalaire::STATUT_SOUSPAYE;
    public const STATUT_SURPAYE  = PaiementSalaire::STATUT_SURPAYE;

    /** Tolérance d'égalité entre deux montants (arrondi). */
    private const MONTANT_TOLERANCE = 0.01;

    /** Précision des arrondis monétaires (2 décimales). */
    private const MONTANT_PRECISION = 2;

    public function __construct(
        private AvanceService $avanceService
    ) {}

    // ============================================================
    // CRÉATION MULTIPLE
    // ============================================================

    /**
     * Crée plusieurs paiements (multi-employés, multi-mois).
     *
     * @param  array<int, array<string, mixed>>  $paiementsData
     *
     * @throws ValidationException
     */
    public function creerPaiements(array $paiementsData, float $tauxChange): void
    {
        if (empty($paiementsData)) {
            throw ValidationException::withMessages([
                'paiements' => 'Aucun paiement à enregistrer.',
            ]);
        }

        DB::transaction(function () use ($paiementsData, $tauxChange) {
            foreach ($paiementsData as $index => $data) {
                try {
                    $this->creerUnPaiement($data, $tauxChange);
                } catch (ValidationException $e) {
                    // Préfixe les erreurs avec l'index pour identifier la ligne fautive
                    throw ValidationException::withMessages(
                        collect($e->errors())
                            ->mapWithKeys(fn ($messages, $key) => [
                                "paiements.{$index}.{$key}" => $messages,
                            ])
                            ->toArray()
                    );
                }
            }
        });
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
        PaiementSalaire $paiementSalaire,
        array $data,
        float $tauxChange
    ): void {
        DB::transaction(function () use ($paiementSalaire, $data, $tauxChange) {
            $user = User::findOrFail($data['user_id']);
            $moisId = (int) $data['mois_scolaire_id'];

            // Unicité (hors paiement courant)
            if ($this->paiementExiste($user->id, $moisId, $paiementSalaire->id)) {
                throw ValidationException::withMessages([
                    'user_id' => 'Un autre paiement existe déjà pour cet employé et ce mois.',
                ]);
            }

            // Annule les remboursements précédents (restaure les dettes)
            $this->avanceService->annulerRemboursements($paiementSalaire);

            // Recalcule les montants
            [$montants, $motifEcartType] = $this->preparerMontants(
                $user,
                (float) $data['montant_paye_usd'],
                $moisId,
                $data['motif_ecart'] ?? null,
                $data['motif_ecart_type'] ?? null,
                $tauxChange,
                $data['date_paiement'] ?? now()->toDateString()
            );

            // Valide le motif si écart
            $this->validerMotifEcart($montants['statut'], $montants['motif_ecart'], $motifEcartType);

            // Met à jour
            $paiementSalaire->update($montants);

            // Remboursements FIFO
            $this->effectuerRemboursement($user, $montants, $paiementSalaire, $tauxChange);

            Log::debug('Paiement salaire mis à jour', [
                'paiement_id' => $paiementSalaire->id,
                'user_id'     => $user->id,
                'statut'      => $montants['statut'],
                'admin_id'    => auth()->id(),
            ]);
        });
    }

    // ============================================================
    // SUPPRESSION
    // ============================================================

    /**
     * Supprime un paiement et annule les remboursements associés.
     */
    public function supprimerPaiement(PaiementSalaire $paiementSalaire): void
    {
        DB::transaction(function () use ($paiementSalaire) {
            $paiementId = $paiementSalaire->id;
            $userId     = $paiementSalaire->user_id;

            // Restaure les dettes AVANT suppression
            $this->avanceService->annulerRemboursements($paiementSalaire);

            $paiementSalaire->delete();

            Log::debug('Paiement salaire supprimé', [
                'paiement_id' => $paiementId,
                'user_id'     => $userId,
                'admin_id'    => auth()->id(),
            ]);
        });
    }

    // ============================================================
    // MÉTHODES PRIVÉES — CRÉATION
    // ============================================================

    /**
     * Crée un seul paiement.
     *
     * @throws ValidationException
     */
    private function creerUnPaiement(array $data, float $tauxChange): PaiementSalaire
    {
        if (empty($data['user_id']) || empty($data['mois_scolaire_id'])) {
            throw ValidationException::withMessages([
                'user_id' => 'L\'employé et le mois sont obligatoires.',
            ]);
        }

        $user = User::findOrFail($data['user_id']);
        $mois = MoisScolaire::findOrFail($data['mois_scolaire_id']);

        // Vérifie l'unicité
        if ($this->paiementExiste($user->id, $mois->id)) {
            $moisLabel = $mois->nom_mois ?? $mois->mois;
            throw ValidationException::withMessages([
                'user_id' => "Un paiement existe déjà pour {$user->name} et le mois {$moisLabel}.",
            ]);
        }

        // Calcule les montants
        [$montants, $motifEcartType] = $this->preparerMontants(
            $user,
            (float) $data['montant_paye_usd'],
            $mois->id,
            $data['motif_ecart'] ?? null,
            $data['motif_ecart_type'] ?? null,
            $tauxChange,
            $data['date_paiement'] ?? now()->toDateString()
        );

        // Valide le motif si écart
        $this->validerMotifEcart($montants['statut'], $montants['motif_ecart'], $motifEcartType);

        // Création
        $paiement = PaiementSalaire::create($montants);

        // Remboursements FIFO
        $this->effectuerRemboursement($user, $montants, $paiement, $tauxChange);

        Log::debug('Paiement salaire créé', [
            'paiement_id' => $paiement->id,
            'user_id'     => $user->id,
            'mois_id'     => $mois->id,
            'statut'      => $montants['statut'],
            'admin_id'    => auth()->id(),
        ]);

        return $paiement;
    }

    // ============================================================
    // MÉTHODES PRIVÉES — CALCUL DES MONTANTS
    // ============================================================

    /**
     * Calcule tous les montants nécessaires à la création / mise à jour.
     *
     * @return array{0: array<string, mixed>, 1: ?string}  [montants, motifEcartType]
     *
     * @throws ValidationException
     */
    private function preparerMontants(
        User $user,
        float $montantPayeUsd,
        int $moisScolaireId,
        ?string $motifEcart,
        ?string $motifEcartType,
        float $tauxChange,
        string $datePaiement
    ): array {
        $salaire = $this->arrondir($this->avanceService->getSalaireMensuelUsd($user));
        $dette   = $this->arrondir($this->avanceService->getDetteTotale($user));

        // Remboursement automatique : min(salaire, dette)
        $remboursementDette = min($salaire, $dette);
        $reportDette        = max(0, $dette - $remboursementDette);
        $netAPayer          = max(0, $salaire - $remboursementDette);

        $montantPaye = $this->arrondir($montantPayeUsd);

        // Interdit de payer plus que le net à payer
        if ($montantPaye > $netAPayer + self::MONTANT_TOLERANCE) {
            throw ValidationException::withMessages([
                'montant_paye_usd' => sprintf(
                    'Le montant payé (%s USD) ne peut pas dépasser le net à payer (%s USD).',
                    number_format($montantPaye, 2, ',', ' '),
                    number_format($netAPayer, 2, ',', ' ')
                ),
            ]);
        }

        $statut         = $this->determinerStatut($netAPayer, $montantPaye);
        $estPaye        = $statut === self::STATUT_PAYE;
        $montantRestant = $reportDette;

        // ✅ Retourne UNIQUEMENT les colonnes existantes en base
        $montants = [
            'user_id'              => $user->id,
            'mois_scolaire_id'     => $moisScolaireId,
            'montant_attendu_usd'  => $salaire,
            'montant_attendu_fc'   => $this->arrondir($salaire * $tauxChange),
            'montant_paye_usd'     => $montantPaye,
            'montant_paye_fc'      => $this->arrondir($montantPaye * $tauxChange),
            'montant_restant_usd'  => $montantRestant,
            'montant_restant_fc'   => $this->arrondir($montantRestant * $tauxChange),
            'avance_deduite_usd'   => $remboursementDette,
            'avance_deduite_fc'    => $this->arrondir($remboursementDette * $tauxChange),
            'dette_remboursee_usd' => $remboursementDette,
            'dette_remboursee_fc'  => $this->arrondir($remboursementDette * $tauxChange),
            'report_dette_usd'     => $reportDette,
            'report_dette_fc'      => $this->arrondir($reportDette * $tauxChange),
            'est_paye'             => $estPaye,
            'statut'               => $statut,
            'motif_ecart'          => $motifEcart,
            'date_paiement'        => $datePaiement,
        ];

        return [$montants, $motifEcartType];
    }

    /**
     * Valide que le motif d'écart est bien renseigné si le statut n'est pas "paye".
     *
     * @throws ValidationException
     */
    private function validerMotifEcart(
        string $statut,
        ?string $motifEcart,
        ?string $motifEcartType
    ): void {
        if ($statut === self::STATUT_PAYE) {
            return;
        }

        $motifTypeRenseigne  = !blank($motifEcartType);
        $motifTexteRenseigne = !blank($motifEcart);

        if (!$motifTypeRenseigne && !$motifTexteRenseigne) {
            throw ValidationException::withMessages([
                'motif_ecart' => "Le motif d'écart est obligatoire lorsque le montant payé diffère du net à payer.",
            ]);
        }

        // Si le type choisi est "autre", le texte libre est obligatoire
        if ($motifEcartType === 'autre' && !$motifTexteRenseigne) {
            throw ValidationException::withMessages([
                'motif_ecart' => "Veuillez préciser le motif d'écart.",
            ]);
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES — REMBOURSEMENT
    // ============================================================

    /**
     * Effectue les remboursements FIFO et met à jour le paiement avec les montants réels.
     *
     * @param  array<string, mixed>  $montants
     */
    private function effectuerRemboursement(
        User $user,
        array $montants,
        PaiementSalaire $paiement,
        float $tauxChange
    ): void {
        $remboursements = $this->avanceService->rembourserDettes(
            $user,
            (float) $montants['dette_remboursee_usd'],
            $paiement->id,
            $tauxChange
        );

        $totalRembourse = !empty($remboursements)
            ? $this->arrondir(array_sum(array_column($remboursements, 'montant_rembourse_usd')))
            : 0.0;

        $montantPrevu = $this->arrondir((float) $montants['dette_remboursee_usd']);

        // Ajuste si le réel diffère du prévu (cas rare : avances supprimées entre-temps)
        if (abs($totalRembourse - $montantPrevu) > self::MONTANT_TOLERANCE) {
            $paiement->update([
                'dette_remboursee_usd' => $totalRembourse,
                'dette_remboursee_fc'  => $this->arrondir($totalRembourse * $tauxChange),
            ]);

            Log::warning('Montant remboursé ajusté après FIFO', [
                'paiement_id'       => $paiement->id,
                'montant_prevu_usd' => $montantPrevu,
                'montant_reel_usd'  => $totalRembourse,
            ]);
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES — UTILITAIRES
    // ============================================================

    /**
     * Vérifie si un paiement existe déjà pour un employé et un mois.
     */
    private function paiementExiste(int $userId, int $moisScolaireId, ?int $excludeId = null): bool
    {
        return PaiementSalaire::query()
            ->where('user_id', $userId)
            ->where('mois_scolaire_id', $moisScolaireId)
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->exists();
    }

    /**
     * Détermine le statut du paiement.
     */
    private function determinerStatut(float $attendu, float $paye): string
    {
        if (abs($attendu - $paye) < self::MONTANT_TOLERANCE) {
            return self::STATUT_PAYE;
        }

        return $paye < $attendu ? self::STATUT_SOUSPAYE : self::STATUT_SURPAYE;
    }

    /**
     * Arrondit un montant à 2 décimales.
     *
     * ⚠️ Correction : avant, la méthode retournait un `int`, perdant les centimes.
     *    Maintenant elle retourne un `float` arrondi à 2 décimales.
     */
    private function arrondir(float $montant): float
    {
        return round($montant, self::MONTANT_PRECISION);
    }
}