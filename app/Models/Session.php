<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    protected $fillable = [
        'nom',
        'annee_scolaire_id', // Ajout
        'description',
        'date_debut',
        'date_fin',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Année scolaire à laquelle appartient cette session.
     */
    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    /**
     * Sections rattachées à cette session.
     */
    public function sections(): HasMany
    {
        return $this->hasMany(Section::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filtrer les sessions par année scolaire.
     */
    public function scopePourAnnee($query, int $anneeId)
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }

    /**
     * Filtrer les sessions actives (non clôturées).
     */
    public function scopeActives($query)
    {
        return $query->whereHas('anneeScolaire', fn($q) => $q->where('cloturee', false));
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------------
    */

    /**
     * Nom complet avec année.
     */
    public function getNomCompletAttribute(): string
    {
        $annee = $this->anneeScolaire?->libelle;
        return $annee ? "{$this->nom} ({$annee})" : $this->nom;
    }
}