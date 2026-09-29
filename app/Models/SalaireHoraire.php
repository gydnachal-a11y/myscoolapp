<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaireHoraire extends Model
{
    protected $table = 'salaire_horaires';

    protected $fillable = ['taux_usd', 'actif'];

    protected $casts = [
        'actif' => 'boolean',
        'taux_usd' => 'decimal:2',
    ];
}