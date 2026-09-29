<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NombreHeure extends Model
{
    protected $fillable = ['valeur', 'libelle'];

    public function coursSalles()
    {
        return $this->hasMany(CourSalle::class, 'nombre_heure_id');
    }
}