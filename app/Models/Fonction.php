<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Fonction (poste) occupé par un utilisateur dans l'établissement.
 *
 * @property int $id
 * @property string $nom
 * @property string|null $description
 * @property int|null $section_id
 * @property-read Section|null $section
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 */
class Fonction extends Model
{
    /**
     * Les attributs qui sont assignables en masse.
     */
    protected $fillable = [
        'nom',
        'description',
        'section_id',
    ];

    /**
     * Les attributs à caster.
     */
    protected $casts = [
        'section_id' => 'integer',
    ];

    // ==========================================
    // Relations
    // ==========================================

    /**
     * La section à laquelle appartient cette fonction.
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * Les utilisateurs occupant cette fonction.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Filtre les fonctions appartenant à une section donnée.
     */
    public function scopePourSection($query, int $sectionId)
    {
        return $query->where('section_id', $sectionId);
    }

    /**
     * Filtre les fonctions par nom (recherche partielle).
     */
    public function scopeRecherche($query, string $terme)
    {
        return $query->where('nom', 'LIKE', "%{$terme}%")
                     ->orWhere('description', 'LIKE', "%{$terme}%");
    }

    // ==========================================
    // Accesseurs
    // ==========================================

    /**
     * Retourne le nom de la fonction (alias).
     */
    public function getLibelleAttribute(): string
    {
        return $this->nom;
    }

    // ==========================================
    // Méthodes utilitaires
    // ==========================================

    /**
     * Vérifie si cette fonction est utilisée par au moins un utilisateur.
     */
    public function estUtilisee(): bool
    {
        return $this->users()->exists();
    }
}