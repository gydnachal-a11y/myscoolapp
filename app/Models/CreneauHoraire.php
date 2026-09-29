<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CreneauHoraire extends Model
{
    /**
     * Nom de la table associée (car la pluralisation n'est pas standard).
     */
    protected $table = 'creneaux_horaires';

    protected $fillable = [
        'libelle',
        'heure_debut',
        'heure_fin',
        'ordre',
    ];

    protected $casts = [
        'heure_debut' => 'datetime:H:i',
        'heure_fin'   => 'datetime:H:i',
    ];

    /**
     * Relation : un créneau horaire peut être utilisé par plusieurs assignations.
     */
    public function coursSalles(): HasMany
    {
        return $this->hasMany(CourSalle::class);
    }

    /**
     * Accesseur pour afficher la plage horaire complète.
     */
    public function getPlageAttribute(): string
    {
        return $this->heure_debut->format('H:i') . ' - ' . $this->heure_fin->format('H:i');
    }
}