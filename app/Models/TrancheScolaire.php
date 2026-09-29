<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrancheScolaire extends Model
{
    protected $table = 'tranches_scolaires';

    protected $fillable = [
        'annee_scolaire_id',
        'tranche',
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

    /**
     * Tranches qui contiennent la date du jour.
     */
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '<=', today())
                     ->whereDate('date_fin', '>=', today());
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Période formatée (fusion date_debut + date_fin).
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
     * Durée en jours.
     */
    public function getDureeJoursAttribute(): ?int
    {
        if (!$this->date_debut || !$this->date_fin) {
            return null;
        }

        return $this->date_debut->diffInDays($this->date_fin);
    }

    /**
     * Indique si la tranche est actuellement active.
     */
    public function getEstEnCoursAttribute(): bool
    {
        return $this->date_debut
            && $this->date_fin
            && $this->date_debut->lte(today())
            && $this->date_fin->gte(today());
    }
}