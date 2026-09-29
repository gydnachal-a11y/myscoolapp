<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalleDeClasse extends Model
{
    protected $table = 'salles_de_classe';

    protected $fillable = [
        'nom',
        'section_id',
        'option_id',
        'capacite_max',
        'frais_inscription',
        'frais_annuel',
        'age_min',
        'age_max',
        'description',
        'salle_superieure_id',
        'mode_paiement',
        'frais_scolarite_mensuel',
        'nombre_tranches',
        'frais_par_tranche', 
    ];

    protected $casts = [
        'frais_scolarite_mensuel' => 'decimal:2',
        'nombre_tranches' => 'integer',
        'frais_inscription' => 'float',
        'frais_annuel' => 'float',
        'capacite_max' => 'integer',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'frais_par_tranche' => 'float',
    ];

    // Constantes pour le mode de paiement
    public const MODE_MENSUEL = 'mensuel';
    public const MODE_TRANCHE = 'tranche';

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Année scolaire via section → session → annee_scolaire.
     */
    public function anneeScolaire()
    {
        return $this->hasOneThrough(
            AnneeScolaire::class,
            Section::class,
            'id',           // clé étrangère sur Section (local)
            'id',           // clé étrangère sur AnneeScolaire (local)
            'section_id',   // clé locale sur SalleDeClasse
            'session_id'    // clé étrangère sur Section (pour remonter)
        );
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(Option::class);
    }

    public function salleSuperieure(): BelongsTo
    {
        return $this->belongsTo(SalleDeClasse::class, 'salle_superieure_id');
    }

    public function inscriptions(): HasMany
    {
        return $this->hasMany(Inscription::class, 'salle_classe_id');
    }

    public function cours(): BelongsToMany
    {
        return $this->belongsToMany(Cour::class, 'cours_salle', 'salle_classe_id', 'cours_id')
                    ->withPivot(['id', 'libelle_id', 'nombre_heure_id', 'ponderation_id', 'titulaire_id', 'jours'])
                    ->withTimestamps();
    }

    public function echeances(): HasMany
    {
        return $this->hasMany(Echeance::class, 'salle_classe_id');
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(Paiement::class, 'salle_classe_id');
    }

    public function fraisSupplementaires(): BelongsToMany
    {
        return $this->belongsToMany(
            FraisSupplementaire::class,
            'frais_supplementaire_salle',
            'salle_classe_id',
            'frais_supplementaire_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeModeMensuel(Builder $query): Builder
    {
        return $query->where('mode_paiement', self::MODE_MENSUEL);
    }

    public function scopeModeTranche(Builder $query): Builder
    {
        return $query->where('mode_paiement', self::MODE_TRANCHE);
    }

    public function scopeRecherche(Builder $query, string $recherche): Builder
    {
        return $query->where('nom', 'like', "%{$recherche}%")
                     ->orWhereHas('section', fn($q) => $q->where('nom', 'like', "%{$recherche}%"));
    }

    /**
     * Filtrer par année scolaire (via section → session).
     */
    public function scopeOfAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->whereHas('section.session', fn($q) => $q->where('annee_scolaire_id', $anneeId));
    }

    /**
     * Filtrer par session (via section).
     */
    public function scopeOfSession(Builder $query, int $sessionId): Builder
    {
        return $query->whereHas('section', fn($q) => $q->where('session_id', $sessionId));
    }

    /*
    |--------------------------------------------------------------------------
    | Accesseurs
    |--------------------------------------------------------------------------
    */

    public function getNomCompletAttribute(): string
    {
        $section = $this->section?->nom;
        return $section ? "{$this->nom} ({$section})" : $this->nom;
    }

    public function getTotalFraisAttribute(): float
    {
        return (float) $this->frais_inscription + (float) $this->frais_annuel;
    }

    /**
     * Vérifie si cette salle est compatible avec une année donnée.
     */
    public function estCompatibleAvecAnnee(int $anneeId): bool
    {
        return $this->anneeScolaire?->id === $anneeId;
    }
}