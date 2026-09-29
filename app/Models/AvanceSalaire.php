<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvanceSalaire extends Model
{
    use HasFactory;

    // ============================================================
    // CONSTANTES — STATUTS
    // ============================================================

    public const STATUT_EN_ATTENTE               = 'en_attente';
    public const STATUT_PARTIELLEMENT_REMBOURSEE = 'partiellement_remboursee';
    public const STATUT_REMBOURSEE               = 'remboursee';
    public const STATUT_ANNULEE                  = 'annulee';

    /**
     * Tous les statuts disponibles.
     */
    public const STATUTS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_PARTIELLEMENT_REMBOURSEE,
        self::STATUT_REMBOURSEE,
        self::STATUT_ANNULEE,
    ];

    /**
     * Statuts d'une avance encore active (non soldée).
     */
    public const STATUTS_ACTIFS = [
        self::STATUT_EN_ATTENTE,
        self::STATUT_PARTIELLEMENT_REMBOURSEE,
    ];

    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected $fillable = [
        'user_id',
        'mois_scolaire_id',
        'montant_avance_usd',
        'montant_avance_fc',
        'date_avance',
        'motif',
        'statut',
        'commentaire',
        'montant_rembourse_usd',
        'montant_rembourse_fc',
        'dette_restante_usd',
        'dette_restante_fc',
    ];

    protected $casts = [
        'date_avance'           => 'date',
        'montant_avance_usd'    => 'float',
        'montant_avance_fc'     => 'float',
        'montant_rembourse_usd' => 'float',
        'montant_rembourse_fc'  => 'float',
        'dette_restante_usd'    => 'float',
        'dette_restante_fc'     => 'float',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * Le membre bénéficiaire de l'avance.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Le mois scolaire associé à l'avance.
     */
    public function moisScolaire(): BelongsTo
    {
        return $this->belongsTo(MoisScolaire::class);
    }

    /**
     * Les remboursements effectués sur cette avance.
     */
    public function remboursements(): HasMany
    {
        return $this->hasMany(RemboursementAvance::class, 'avance_id');
    }

    /**
     * La demande d'avance d'origine (si créée via une demande membre).
     */
    public function demandeOrigine(): HasMany
    {
        return $this->hasMany(DemandeAvance::class, 'avance_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Avances actives (en attente ou partiellement remboursées).
     */
    public function scopeActives(Builder $q): Builder
    {
        return $q->whereIn('statut', self::STATUTS_ACTIFS);
    }

    /**
     * Avances en attente (aucun remboursement effectué).
     */
    public function scopeEnAttente(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_EN_ATTENTE);
    }

    /**
     * Avances partiellement remboursées.
     */
    public function scopePartiellementRemboursees(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_PARTIELLEMENT_REMBOURSEE);
    }

    /**
     * Avances entièrement remboursées.
     */
    public function scopeRemboursees(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_REMBOURSEE);
    }

    /**
     * Avances annulées.
     */
    public function scopeAnnulees(Builder $q): Builder
    {
        return $q->where('statut', self::STATUT_ANNULEE);
    }

    /**
     * Avances soldées (remboursées OU annulées).
     */
    public function scopeSoldees(Builder $q): Builder
    {
        return $q->whereIn('statut', [
            self::STATUT_REMBOURSEE,
            self::STATUT_ANNULEE,
        ]);
    }

    /**
     * Avances d'un utilisateur donné.
     */
    public function scopeForUser(Builder $q, int $userId): Builder
    {
        return $q->where('user_id', $userId);
    }

    /**
     * Avances triées par date (FIFO : la plus ancienne en premier).
     */
    public function scopeFifo(Builder $q): Builder
    {
        return $q->orderBy('date_avance', 'asc');
    }

    /**
     * Avances récentes.
     */
    public function scopeRecentes(Builder $q, int $jours = 30): Builder
    {
        return $q->where('date_avance', '>=', now()->subDays($jours));
    }

    // ============================================================
    // MÉTHODES D'INSTANCE — VÉRIFICATION DE STATUT
    // ============================================================

    public function isEnAttente(): bool
    {
        return $this->statut === self::STATUT_EN_ATTENTE;
    }

    public function isPartiellementRemboursee(): bool
    {
        return $this->statut === self::STATUT_PARTIELLEMENT_REMBOURSEE;
    }

    public function isRemboursee(): bool
    {
        return $this->statut === self::STATUT_REMBOURSEE;
    }

    public function isAnnulee(): bool
    {
        return $this->statut === self::STATUT_ANNULEE;
    }

    /**
     * Vérifie si l'avance est encore active (non soldée).
     */
    public function isActive(): bool
    {
        return in_array($this->statut, self::STATUTS_ACTIFS, true);
    }

    /**
     * Vérifie si l'avance est totalement soldée (remboursée ou annulée).
     */
    public function isSoldee(): bool
    {
        return $this->isRemboursee() || $this->isAnnulee();
    }

    /**
     * Vérifie si l'avance peut être modifiée (montant, motif, etc.).
     * Une avance ne peut être modifiée que si aucun remboursement n'a été effectué.
     */
    public function peutEtreModifiee(): bool
    {
        return $this->isEnAttente()
            && (float) $this->montant_rembourse_usd === 0.0;
    }

    /**
     * Vérifie si l'avance peut être supprimée.
     */
    public function peutEtreSupprimee(): bool
    {
        return !$this->remboursements()->exists();
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Libellé humain du statut.
     */
    public function getStatutLabelAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE               => 'En attente',
            self::STATUT_PARTIELLEMENT_REMBOURSEE => 'Partiellement remboursée',
            self::STATUT_REMBOURSEE               => 'Remboursée',
            self::STATUT_ANNULEE                  => 'Annulée',
            default                               => ucfirst((string) $this->statut),
        };
    }

    /**
     * Clé CSS du statut (pour les badges).
     */
    public function getStatutKeyAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE               => 'warning',
            self::STATUT_PARTIELLEMENT_REMBOURSEE => 'info',
            self::STATUT_REMBOURSEE               => 'success',
            self::STATUT_ANNULEE                  => 'neutral',
            default                               => 'neutral',
        };
    }

    /**
     * Icône Font Awesome associée au statut.
     */
    public function getStatutIconAttribute(): string
    {
        return match ($this->statut) {
            self::STATUT_EN_ATTENTE               => 'fa-hourglass-half',
            self::STATUT_PARTIELLEMENT_REMBOURSEE => 'fa-circle-half-stroke',
            self::STATUT_REMBOURSEE               => 'fa-circle-check',
            self::STATUT_ANNULEE                  => 'fa-ban',
            default                               => 'fa-circle',
        };
    }

    /**
     * Pourcentage de remboursement (0-100).
     */
    public function getPourcentageRembourseAttribute(): float
    {
        $montantInitial = (float) $this->montant_avance_usd;

        if ($montantInitial <= 0) {
            return 0.0;
        }

        $pourcentage = ((float) $this->montant_rembourse_usd / $montantInitial) * 100;

        return round(min(100, max(0, $pourcentage)), 2);
    }

    /**
     * Montant restant à rembourser en USD (arrondi).
     */
    public function getResteARembourserUsdAttribute(): float
    {
        $montantInitial = (float) $this->montant_avance_usd;
        $rembourse      = (float) $this->montant_rembourse_usd;

        return max(0, round($montantInitial - $rembourse, 2));
    }

    /**
     * Nombre de jours écoulés depuis la date de l'avance.
     */
    public function getJoursEcoulesAttribute(): int
    {
        if (!$this->date_avance) {
            return 0;
        }

        return $this->date_avance->diffInDays(today(), false);
    }

    /**
     * Durée de remboursement complète (en jours).
     * Retourne null si non encore remboursée.
     */
    public function getDureeRemboursementAttribute(): ?int
    {
        if (!$this->isRemboursee() || !$this->date_avance) {
            return null;
        }

        // Utilise la date du dernier remboursement
        $dernier = $this->remboursements()->latest('date_remboursement')->first();

        if (!$dernier || !$dernier->date_remboursement) {
            return null;
        }

        return $this->date_avance->diffInDays($dernier->date_remboursement);
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    /**
     * Applique un remboursement et met à jour les montants et le statut.
     *
     * @param  float  $montantUsd  Montant remboursé (USD)
     * @param  float  $tauxChange  Taux de change appliqué
     * @return bool
     */
    public function appliquerRemboursement(float $montantUsd, float $tauxChange): bool
    {
        $montantUsd = round($montantUsd, 2);

        if ($montantUsd <= 0 || $montantUsd > $this->reste_a_rembourser_usd) {
            return false;
        }

        $nouveauRembourse = round((float) $this->montant_rembourse_usd + $montantUsd, 2);
        $nouvelleDette    = round(max(0, (float) $this->montant_avance_usd - $nouveauRembourse), 2);

        $statut = $nouvelleDette <= 0
            ? self::STATUT_REMBOURSEE
            : self::STATUT_PARTIELLEMENT_REMBOURSEE;

        return $this->update([
            'montant_rembourse_usd' => $nouveauRembourse,
            'montant_rembourse_fc'  => round($nouveauRembourse * $tauxChange, 2),
            'dette_restante_usd'    => $nouvelleDette,
            'dette_restante_fc'     => round($nouvelleDette * $tauxChange, 2),
            'statut'                => $statut,
        ]);
    }

    /**
     * Annule l'avance (si elle n'a pas de remboursements).
     */
    public function annuler(): bool
    {
        if (!$this->peutEtreSupprimee()) {
            return false;
        }

        return $this->update(['statut' => self::STATUT_ANNULEE]);
    }

    // ============================================================
    // BOOT — ÉVÉNEMENTS
    // ============================================================

    /**
     * Invalide le cache de dette de l'utilisateur après modification.
     */
    protected static function booted(): void
    {
        $invalider = function (self $avance) {
            if ($avance->user_id) {
                cache()->forget('dettes_actives_user_' . $avance->user_id);
            }
        };

        static::saved($invalider);
        static::deleted($invalider);
    }
}