<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Paiement extends Model
{
    // ============================================================
    // CONSTANTES — Statuts
    // ============================================================
    public const STATUT_IMPAYE  = 'impaye';
    public const STATUT_PARTIEL = 'partiel';
    public const STATUT_PAYE    = 'paye';
    public const STATUT_SURPAYE = 'surpaye';

    /** Liste des statuts valides (pour la validation). */
    public const STATUTS = [
        self::STATUT_IMPAYE,
        self::STATUT_PARTIEL,
        self::STATUT_PAYE,
        self::STATUT_SURPAYE,
    ];

    // ============================================================
    // CONSTANTES — Modes de période
    // ============================================================
    public const MODE_MENSUEL = 'mensuel';
    public const MODE_TRANCHE = 'tranche';

    public const MODES = [
        self::MODE_MENSUEL,
        self::MODE_TRANCHE,
    ];

    protected $fillable = [
        'eleve_id',
        'annee_scolaire_id',
        'salle_classe_id',
        'session_paiement_id',
        'type_periode',
        'periode',
        'montant_attendu_usd',
        'montant_attendu_fc',
        'montant_paye_usd',
        'montant_paye_fc',
        'montant_restant_usd',
        'montant_restant_fc',
        'statut',
        'date_paiement',
        'commentaire',
    ];

    protected $casts = [
        'date_paiement'       => 'date',
        'montant_attendu_usd' => 'float',
        'montant_attendu_fc'  => 'float',
        'montant_paye_usd'    => 'float',
        'montant_paye_fc'     => 'float',
        'montant_restant_usd' => 'float',
        'montant_restant_fc'  => 'float',
    ];

    /**
     * Attributs calculés à exposer en JSON / API.
     */
    protected $appends = [
        'montant_attendu_usd_formate',
        'montant_paye_usd_formate',
        'montant_restant_usd_formate',
    ];

    // ============================================================
    // BOOT — Recalcul automatique des montants et du statut
    // ============================================================
    protected static function booted(): void
    {
        static::saving(function (Paiement $paiement) {
            $paiement->recalculerMontants();

            // Date par défaut : aujourd'hui si non fournie
            if (empty($paiement->date_paiement) && $paiement->montant_paye_usd > 0) {
                $paiement->date_paiement = now()->toDateString();
            }
        });
    }

    // ============================================================
    // RELATIONS
    // ============================================================

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function salleClasse(): BelongsTo
    {
        return $this->belongsTo(SalleDeClasse::class, 'salle_classe_id');
    }

    public function sessionPaiement(): BelongsTo
    {
        return $this->belongsTo(SessionPaiement::class, 'session_paiement_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }

    public function scopeSalle(Builder $query, int $salleId): Builder
    {
        return $query->where('salle_classe_id', $salleId);
    }

    public function scopeEleve(Builder $query, int $eleveId): Builder
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    public function scopePaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PAYE);
    }

    public function scopePartiel(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PARTIEL);
    }

    public function scopeImpaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_IMPAYE);
    }

    public function scopeSurpaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_SURPAYE);
    }

    public function scopeMensuel(Builder $query): Builder
    {
        return $query->where('type_periode', self::MODE_MENSUEL);
    }

    public function scopeTranche(Builder $query): Builder
    {
        return $query->where('type_periode', self::MODE_TRANCHE);
    }

    /**
     * Recherche par nom/prénom d'élève.
     */
    public function scopeRecherche(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        return $query->whereHas('eleve', function (Builder $q) use ($search) {
            $q->where('nom', 'LIKE', "%{$search}%")
              ->orWhere('prenom', 'LIKE', "%{$search}%")
              ->orWhere('postnom', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Un paiement en attente (impayé ou partiel).
     */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->whereIn('statut', [self::STATUT_IMPAYE, self::STATUT_PARTIEL]);
    }

    // ============================================================
    // ACCESSORS — Montants formatés
    // ============================================================

    protected function montantAttenduUsdFormate(): Attribute
    {
        return Attribute::get(fn () => number_format((float) $this->montant_attendu_usd, 0, ',', ' '));
    }

    protected function montantPayeUsdFormate(): Attribute
    {
        return Attribute::get(fn () => number_format((float) $this->montant_paye_usd, 0, ',', ' '));
    }

    protected function montantRestantUsdFormate(): Attribute
    {
        return Attribute::get(fn () => number_format((float) $this->montant_restant_usd, 0, ',', ' '));
    }

    protected function montantAttenduFcFormate(): Attribute
    {
        return Attribute::get(fn () => number_format((float) $this->montant_attendu_fc, 0, ',', ' '));
    }

    protected function montantPayeFcFormate(): Attribute
    {
        return Attribute::get(fn () => number_format((float) $this->montant_paye_fc, 0, ',', ' '));
    }

    protected function montantRestantFcFormate(): Attribute
    {
        return Attribute::get(fn () => number_format((float) $this->montant_restant_fc, 0, ',', ' '));
    }

    /**
     * Taux d'avancement du paiement (0-100 %).
     */
    protected function pourcentagePaye(): Attribute
    {
        return Attribute::get(function () {
            $attendu = (float) $this->montant_attendu_usd;

            if ($attendu <= 0) {
                return 0;
            }

            return min(100, round(($this->montant_paye_usd / $attendu) * 100, 1));
        });
    }

    // ============================================================
    // MÉTHODES UTILITAIRES
    // ============================================================

    public function estPaye(): bool
    {
        return $this->statut === self::STATUT_PAYE;
    }

    public function estPartiel(): bool
    {
        return $this->statut === self::STATUT_PARTIEL;
    }

    public function estImpaye(): bool
    {
        return $this->statut === self::STATUT_IMPAYE;
    }

    public function estSurpaye(): bool
    {
        return $this->statut === self::STATUT_SURPAYE;
    }

    /**
     * Recalcule tous les montants restants et le statut.
     * Appelé automatiquement au `saving` + utilisable manuellement.
     */
    public function recalculerMontants(): void
    {
        $attenduUsd = (float) $this->montant_attendu_usd;
        $payeUsd    = (float) $this->montant_paye_usd;

        // Restant USD (jamais négatif)
        $this->montant_restant_usd = max(0, $attenduUsd - $payeUsd);

        // Restant FC (si le champ est présent)
        if ($this->montant_attendu_fc !== null || $this->montant_paye_fc !== null) {
            $attenduFc = (float) ($this->montant_attendu_fc ?? 0);
            $payeFc    = (float) ($this->montant_paye_fc ?? 0);
            $this->montant_restant_fc = max(0, $attenduFc - $payeFc);
        }

        $this->statut = $this->determinerStatut();
    }

    /**
     * Détermine le statut en fonction des montants.
     */
    private function determinerStatut(): string
    {
        $attendu = (float) $this->montant_attendu_usd;
        $paye    = (float) $this->montant_paye_usd;

        // Cas dégénéré : rien d'attendu
        if ($attendu <= 0) {
            return $paye > 0 ? self::STATUT_SURPAYE : self::STATUT_IMPAYE;
        }

        if ($paye >= $attendu) {
            return $paye > $attendu ? self::STATUT_SURPAYE : self::STATUT_PAYE;
        }

        return $paye > 0 ? self::STATUT_PARTIEL : self::STATUT_IMPAYE;
    }
}