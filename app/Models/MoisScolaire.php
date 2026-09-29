<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoisScolaire extends Model
{
    protected $table = 'mois_scolaires';

    protected $fillable = [
        'annee_scolaire_id',
        'mois',
        'nom_mois',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function paiementSalaires(): HasMany
    {
        return $this->hasMany(PaiementSalaire::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    /**
     * Filtre par année scolaire.
     */
    public function scopeDeAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }

    /**
     * Tri chronologique par date de début.
     */
    public function scopeChronologique(Builder $query): Builder
    {
        return $query->orderBy('date_debut');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Libellé complet prêt pour l'affichage.
     */
    public function getLibelleCompletAttribute(): string
    {
        return $this->nom_mois
            ? "{$this->nom_mois} ({$this->mois})"
            : $this->mois;
    }

    /**
     * Période formatée.
     */
    public function getPeriodeAttribute(): string
    {
        if (!$this->date_debut || !$this->date_fin) {
            return 'Dates non définies';
        }

        return $this->date_debut->translatedFormat('d M')
            . ' → '
            . $this->date_fin->translatedFormat('d M Y');
    }
}