<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Section extends Model
{
    protected $fillable = [
        'nom',
        'session_id',
        'description',
        'code', // optionnel
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * Session parente.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    /**
     * Salles de classe de cette section.
     */
    public function sallesDeClasse(): HasMany
    {
        return $this->hasMany(SalleDeClasse::class);
    }

    /**
     * Utilisateurs (personnel) rattachés à cette section.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'section_id');
    }

    /**
     * Année scolaire via la session (relation indirecte).
     */
    public function anneeScolaire()
    {
        return $this->hasOneThrough(
            AnneeScolaire::class,
            Session::class,
            'id',            // clé étrangère sur Session (local)
            'id',            // clé étrangère sur AnneeScolaire (local)
            'session_id',    // clé locale sur Section
            'annee_scolaire_id' // clé étrangère sur Session
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Filtrer par session.
     */
    public function scopeOfSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Filtrer par année scolaire (via session).
     */
    public function scopeOfAnnee($query, int $anneeId)
    {
        return $query->whereHas('session', fn($q) => $q->where('annee_scolaire_id', $anneeId));
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------------
    */

    /**
     * Nom complet avec session.
     */
    public function getNomCompletAttribute(): string
    {
        $session = $this->session?->nom;
        return $session ? "{$this->nom} ({$session})" : $this->nom;
    }
}