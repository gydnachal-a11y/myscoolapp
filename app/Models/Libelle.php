<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Libelle extends Model
{
    protected $fillable = ['nom', 'description'];

    public function coursSalles()
    {
        return $this->hasMany(CourSalle::class, 'libelle_id');
    }
}