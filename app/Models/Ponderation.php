<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ponderation extends Model
{
    protected $fillable = ['nom', 'valeur'];

    protected $casts = [
        'valeur' => 'integer',
    ];

    public function coursSalles()
    {
        return $this->hasMany(CourSalle::class, 'ponderation_id');
    }
}