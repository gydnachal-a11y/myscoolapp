<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class PaiementSalaire extends Model
{
    use HasFactory;
    use SoftDeletes;   // ✅ Activé maintenant que la migration est appliquée

    // ============================================================
    // CONSTANTES — STATUTS
    // ============================================================

    public const STATUT_PAYE     = 'paye';
    public const STATUT_SOUSPAYE = 'souspaye';
    public const STATUT_SURPAYE  = 'surpaye';

    public const STATUTS = [
        self::STATUT_PAYE,
        self::STATUT_SOUSPAYE,
        self::STATUT_SURPAYE,
    ];

    // ============================================================
    // CONSTANTES — CACHE
    // ============================================================

    private const CACHE_PREFIX = 'dettes_actives_user_';

    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected $table = 'paiement_salaires';

    protected $fillable = [
        'user_id',
        'mois_scolaire_id',
        'montant_attendu_usd',
        'montant_attendu_fc',
        'montant_paye_usd',
        'montant_paye_fc',
        'montant_restant_usd',
        'montant_restant_fc',
        'avance_deduite_usd',
        'avance_deduite_fc',
        'dette_remboursee_usd',
        'dette_remboursee_fc',
        'report_dette_usd',
        'report_dette_fc',
        'est_paye',
        'statut',
        'motif_ecart',
        'date_paiement',
    ];

    protected $casts = [
        'est_paye'             => 'boolean',
        'date_paiement'        => 'date',

        'montant_attendu_usd'  => 'float',
        'montant_attendu_fc'   => 'float',
        'montant_paye_usd'     => 'float',
        'montant_paye_fc'      => 'float',
        'montant_restant_usd'  => 'float',
        'montant_restant_fc'   => 'float',

        'avance_deduite_usd'   => 'float',
        'avance_deduite_fc'    => 'float',
        'dette_remboursee_usd' => 'float',
        'dette_remboursee_fc'  => 'float',
        'report_dette_usd'     => 'float',
        'report_dette_fc'      => 'float',
    ];

    /**
     * Valeurs par défaut (évite les `null` inattendus dans les calculs).
     */
    protected $attributes = [
        'montant_attendu_usd'  => 0,
        'montant_attendu_fc'   => 0,
        'montant_paye_usd'     => 0,
        'montant_paye_fc'      => 0,
        'montant_restant_usd'  => 0,
        'montant_restant_fc'   => 0,
        'avance_deduite_usd'   => 0,
        'avance_deduite_fc'    => 0,
        'dette_remboursee_usd' => 0,
        'dette_remboursee_fc'  => 0,
        'report_dette_usd'     => 0,
        'report_dette_fc'      => 0,
        'est_paye'             => false,
        'statut'               => self::STATUT_SOUSPAYE,
    ];

    /**
     * Attributs virtuels exposés lors de la sérialisation JSON / API.
     */
    protected $appends = [
        'statut_label',
        'statut_key',
        'statut_icon',
        'pourcentage_paye',
        'net_a_payer_usd',
        'net_a_payer_fc',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function moisScolaire(): BelongsTo
    {
        return $this->belongsTo(MoisScolaire::class, 'mois_scolaire_id');
    }

    public function remboursementsAvances(): HasMany
    {
        return $this->hasMany(RemboursementAvance::class, 'paiement_salaire_id');
    }

    // ============================================================
    // SCOPES — STATUTS
    // ============================================================

    public function scopePaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PAYE);
    }

    public function scopeSousPaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_SOUSPAYE);
    }

    public function scopeSurPaye(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_SURPAYE);
    }

    public function scopeNonSolde(Builder $query): Builder
    {
        return $query->whereIn('statut', [
            self::STATUT_SOUSPAYE,
            self::STATUT_SURPAYE,
        ]);
    }

    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    // ============================================================
    // SCOPES — FILTRES MÉTIER
    // ============================================================

    public function scopeForUser(Builder $query, User|int $user): Builder
    {
        $userId = $user instanceof User ? $user->id : $user;

        return $query->where('user_id', $userId);
    }

    public function scopeForMois(Builder $query, MoisScolaire|int $mois): Builder
    {
        $moisId = $mois instanceof MoisScolaire ? $mois->id : $mois;

        return $query->where('mois_scolaire_id', $moisId);
    }

    public function scopeEntreDates(Builder $query, string $debut, string $fin): Builder
    {
        return $query->whereBetween('date_paiement', [$debut, $fin]);
    }

    public function scopeRecents(Builder $query, int $jours = 30): Builder
    {
        return $query->where('date_paiement', '>=', now()->subDays($jours));
    }

    public function scopeAvecRemboursements(Builder $query): Builder
    {
        return $query->whereHas('remboursementsAvances');
    }

    public function scopeSansRemboursements(Builder $query): Builder
    {
        return $query->whereDoesntHave('remboursementsAvances');
    }

    public function scopeAvecReport(Builder $query): Builder
    {
        return $query->where('report_dette_usd', '>', 0);
    }

    // ============================================================
    // SCOPES — SOFT DELETES
    // ============================================================

    /**
     * Uniquement les paiements supprimés (corbeille).
     */
    public function scopeCorbeille(Builder $query): Builder
    {
        return $query->onlyTrashed();
    }

    /**
     * Inclut les paiements supprimés dans la requête.
     */
    public function scopeAvecCorbeille(Builder $query): Builder
    {
        return $query->withTrashed();
    }

    // ============================================================
    // MÉTHODES D'INSTANCE — STATUTS
    // ============================================================

    public function isPaye(): bool
    {
        return $this->statut === self::STATUT_PAYE;
    }

    public function isSousPaye(): bool
    {
        return $this->statut === self::STATUT_SOUSPAYE;
    }

    public function isSurPaye(): bool
    {
        return $this->statut === self::STATUT_SURPAYE;
    }

    public function isSolde(): bool
    {
        return $this->isPaye() && (float) $this->montant_restant_usd <= 0;
    }

    public function aUnEcart(): bool
    {
        return !$this->isPaye();
    }

    public function aDesEcarts(): bool
    {
        return $this->aUnEcart();
    }

    /**
     * Le paiement est-il dans la corbeille ?
     */
    public function estSupprime(): bool
    {
        return $this->trashed();
    }

    // ============================================================
    // ACCESSORS — STATUTS
    // ============================================================

    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_PAYE     => 'Payé',
            self::STATUT_SOUSPAYE => 'Sous-payé',
            self::STATUT_SURPAYE  => 'Sur-payé',
            default               => ucfirst((string) $this->statut),
        };
    }

    public function getStatutKeyAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_PAYE     => 'success',
            self::STATUT_SOUSPAYE => 'warning',
            self::STATUT_SURPAYE  => 'danger',
            default               => 'neutral',
        };
    }

    public function getStatutIconAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_PAYE     => 'fa-circle-check',
            self::STATUT_SOUSPAYE => 'fa-arrow-down',
            self::STATUT_SURPAYE  => 'fa-arrow-up',
            default               => 'fa-circle',
        };
    }

    // ============================================================
    // ACCESSORS — CALCULS
    // ============================================================

    public function getNetAPayerUsdAttribute(): float
    {
        return max(0, round(
            (float) $this->montant_attendu_usd
            - (float) $this->avance_deduite_usd
            - (float) $this->dette_remboursee_usd,
            2
        ));
    }

    public function getNetAPayerFcAttribute(): float
    {
        return max(0, round(
            (float) $this->montant_attendu_fc
            - (float) $this->avance_deduite_fc
            - (float) $this->dette_remboursee_fc,
            2
        ));
    }

    public function getPourcentagePayeAttribute(): float
    {
        $attendu = (float) $this->montant_attendu_usd;

        if ($attendu <= 0) {
            return 0.0;
        }

        $pct = ((float) $this->montant_paye_usd / $attendu) * 100;

        return round(min(100, max(0, $pct)), 2);
    }

    public function getAvecReportAttribute(): bool
    {
        return (float) $this->report_dette_usd > 0;
    }

    public function getEcartUsdAttribute(): float
    {
        return round(
            (float) $this->montant_paye_usd - $this->net_a_payer_usd,
            2
        );
    }

    public function getEcartFcAttribute(): float
    {
        return round(
            (float) $this->montant_paye_fc - $this->net_a_payer_fc,
            2
        );
    }

    public function getDatePaiementFormateeAttribute(): string
    {
        return $this->date_paiement?->translatedFormat('d M Y') ?? '—';
    }

    public function getMoisLabelAttribute(): string
    {
        return $this->moisScolaire?->nom_mois
            ?? $this->moisScolaire?->mois
            ?? 'Mois inconnu';
    }

    public function getEcartUsdFormateAttribute(): string
    {
        $ecart = $this->ecart_usd;

        return ($ecart >= 0 ? '+' : '') . number_format($ecart, 2, ',', ' ');
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function calculerStatut(): string
    {
        $paye = (float) $this->montant_paye_usd;
        $net  = $this->net_a_payer_usd;

        if ($net <= 0 && $paye <= 0) {
            return self::STATUT_PAYE;
        }

        if (abs($paye - $net) < 0.01) {
            return self::STATUT_PAYE;
        }

        return $paye < $net ? self::STATUT_SOUSPAYE : self::STATUT_SURPAYE;
    }

    public function mettreAJourStatut(): bool
    {
        $statut = $this->calculerStatut();

        return $this->update([
            'statut'   => $statut,
            'est_paye' => $statut === self::STATUT_PAYE,
        ]);
    }

    public function peutEtreSupprime(): bool
    {
        return !$this->remboursementsAvances()->exists();
    }

    // ============================================================
    // MÉTHODES STATIQUES — CACHE
    // ============================================================

    public static function oublierCacheUtilisateur(int $userId): void
    {
        Cache::forget(self::CACHE_PREFIX . $userId);
    }

    // ============================================================
    // BOOT — ÉVÉNEMENTS
    // ============================================================

    protected static function booted(): void
    {
        // Recalcul automatique du statut et des restants avant sauvegarde
        static::saving(function (self $paiement) {
            $paiement->statut   = $paiement->calculerStatut();
            $paiement->est_paye = $paiement->statut === self::STATUT_PAYE;

            // Recalcul du montant restant USD (sécurité)
            if ($paiement->isDirty([
                'montant_attendu_usd', 'montant_paye_usd',
                'avance_deduite_usd', 'dette_remboursee_usd',
            ])) {
                $paiement->montant_restant_usd = max(
                    0,
                    round((float) $paiement->montant_attendu_usd
                        - (float) $paiement->montant_paye_usd, 2)
                );
            }

            // Recalcul du montant restant FC (sécurité)
            if ($paiement->isDirty([
                'montant_attendu_fc', 'montant_paye_fc',
                'avance_deduite_fc', 'dette_remboursee_fc',
            ])) {
                $paiement->montant_restant_fc = max(
                    0,
                    round((float) $paiement->montant_attendu_fc
                        - (float) $paiement->montant_paye_fc, 2)
                );
            }
        });

        // Invalidation du cache à chaque changement d'état
        $invaliderCache = function (self $paiement) {
            if ($paiement->user_id) {
                self::oublierCacheUtilisateur((int) $paiement->user_id);
            }
        };

        static::saved($invaliderCache);
        static::deleted($invaliderCache);
        static::restored($invaliderCache);   // ✅ Valide maintenant (SoftDeletes activé)
    }
}