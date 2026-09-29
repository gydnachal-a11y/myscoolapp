<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Cour extends Model
{
    protected $fillable = ['nom', 'categorie_id'];

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    public function salles(): BelongsToMany
    {
        return $this->belongsToMany(SalleDeClasse::class, 'cours_salle', 'cours_id', 'salle_classe_id')
            ->withPivot(['id', 'libelle_id', 'nombre_heure_id', 'ponderation_id', 'titulaire_id'])
            ->withTimestamps();
    }
}