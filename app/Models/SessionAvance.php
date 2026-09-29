<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SessionAvance extends Model
{
    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected $fillable = [
        'libelle',
        'date_debut',
        'date_fin',
        'est_active',
        'created_by',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'est_active' => 'boolean',
    ];

    // ============================================================
    // CONSTANTES
    // ============================================================

    private const CACHE_KEY_ACTIVE = 'session_avance_active_id';
    private const CACHE_TTL        = 60; // secondes

    // ============================================================
    // RELATIONS
    // ============================================================

    /**
     * L'administrateur qui a créé la session.
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Les demandes d'avance liées à cette session.
     */
    public function demandes(): HasMany
    {
        return $this->hasMany(DemandeAvance::class, 'session_avance_id');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Sessions actuellement ouvertes (active + dans les dates).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('est_active', true)
            ->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today());
    }

    /**
     * Sessions fermées manuellement.
     */
    public function scopeFermees(Builder $query): Builder
    {
        return $query->where('est_active', false);
    }

    /**
     * Sessions expirées (date de fin passée).
     */
    public function scopeExpirees(Builder $query): Builder
    {
        return $query->whereDate('date_fin', '<', today());
    }

    /**
     * Sessions à venir (date de début future).
     */
    public function scopeAVenir(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '>', today());
    }

    /**
     * Sessions ouvertes selon les dates (sans tenir compte de est_active).
     */
    public function scopeDansPeriode(Builder $query): Builder
    {
        return $query
            ->whereDate('date_debut', '<=', today())
            ->whereDate('date_fin', '>=', today());
    }

    // ============================================================
    // MÉTHODES STATIQUES — CACHE PAR ID (safe)
    // ============================================================

    /**
     * Récupère la session actuellement active.
     *
     * ⚠️ On cache UNIQUEMENT l'ID (pas le modèle) pour éviter
     * l'erreur `__PHP_Incomplete_Class` lors de la désérialisation.
     */
    public static function getActive(): ?self
    {
        $id = cache()->remember(self::CACHE_KEY_ACTIVE, self::CACHE_TTL, function () {
            return static::active()
                ->latest('date_debut')
                ->value('id');
        });

        if (!$id) {
            return null;
        }

        // Recharge le modèle depuis la base (fresh)
        return static::find($id);
    }

    /**
     * Vide le cache de la session active.
     * Appelé automatiquement après création/fermeture/suppression.
     */
    public static function clearActiveCache(): void
    {
        cache()->forget(self::CACHE_KEY_ACTIVE);
    }

    /**
     * Rafraîchit le cache de la session active.
     */
    public static function refreshActiveCache(): ?self
    {
        static::clearActiveCache();

        return static::getActive();
    }

    // ============================================================
    // MÉTHODES D'INSTANCE
    // ============================================================

    /**
     * Vérifie si cette session est actuellement active.
     */
    public function isActive(): bool
    {
        return $this->est_active
            && $this->date_debut
            && $this->date_fin
            && $this->date_debut->lte(today())
            && $this->date_fin->gte(today());
    }

    /**
     * Vérifie si la session est expirée (sans tenir compte de est_active).
     */
    public function isExpiree(): bool
    {
        return $this->date_fin && $this->date_fin->lt(today());
    }

    /**
     * Vérifie si la session est à venir.
     */
    public function isAVenir(): bool
    {
        return $this->date_debut && $this->date_debut->gt(today());
    }

    /**
     * Vérifie si la session peut recevoir des demandes.
     */
    public function peutRecevoirDemandes(): bool
    {
        return $this->isActive();
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Libellé du statut de la session.
     */
    public function getStatutLabelAttribute(): string
    {
        if (!$this->est_active) {
            return 'Fermée manuellement';
        }

        if (!$this->date_debut || !$this->date_fin) {
            return 'Dates non définies';
        }

        if ($this->date_debut->gt(today())) {
            return 'À venir';
        }

        if ($this->date_fin->lt(today())) {
            return 'Expirée';
        }

        return 'Ouverte';
    }

    /**
     * Clé CSS du statut (utile pour les badges).
     */
    public function getStatutKeyAttribute(): string
    {
        if (!$this->est_active) {
            return 'fermee';
        }

        if (!$this->date_debut || !$this->date_fin) {
            return 'inconnu';
        }

        if ($this->date_debut->gt(today())) {
            return 'a_venir';
        }

        if ($this->date_fin->lt(today())) {
            return 'expiree';
        }

        return 'ouverte';
    }

    /**
     * Nombre de jours restants avant la fermeture (négatif si expirée).
     */
    public function getJoursRestantsAttribute(): ?int
    {
        if (!$this->date_fin) {
            return null;
        }

        return today()->diffInDays($this->date_fin, false);
    }

    /**
     * Durée totale de la session en jours.
     */
    public function getDureeJoursAttribute(): ?int
    {
        if (!$this->date_debut || !$this->date_fin) {
            return null;
        }

        return $this->date_debut->diffInDays($this->date_fin);
    }

    /**
     * Plage de dates formatée.
     */
    public function getPeriodeAttribute(): string
    {
        if (!$this->date_debut || !$this->date_fin) {
            return 'Dates non définies';
        }

        return $this->date_debut->translatedFormat('d M Y')
            . ' → '
            . $this->date_fin->translatedFormat('d M Y');
    }

    /**
     * Indique si la session est dans la période mais désactivée manuellement.
     */
    public function getEstFermeeManuellementAttribute(): bool
    {
        return !$this->est_active
            && $this->date_debut
            && $this->date_fin
            && $this->date_debut->lte(today())
            && $this->date_fin->gte(today());
    }

    // ============================================================
    // BOOT — Nettoyage automatique du cache
    // ============================================================

    /**
     * Invalide le cache de la session active après toute modification.
     */
    protected static function booted(): void
    {
        static::saved(function (self $session) {
            static::clearActiveCache();
        });

        static::deleted(function (self $session) {
            static::clearActiveCache();
        });
    }
}