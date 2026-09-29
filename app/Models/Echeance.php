<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Echeance extends Model
{
    protected $fillable = [
        'eleve_id',
        'annee_scolaire_id',
        'salle_classe_id',
        'type_periode',
        'periode',
        'montant_usd',
        'montant_fc',
        'est_paye',
        'date_echeance',
    ];

    protected $casts = [
        'est_paye' => 'boolean',
        'date_echeance' => 'date',
    ];

    public function eleve(): BelongsTo { return $this->belongsTo(Eleve::class); }
    public function anneeScolaire(): BelongsTo { return $this->belongsTo(AnneeScolaire::class); }
    public function salleClasse(): BelongsTo { return $this->belongsTo(SalleDeClasse::class, 'salle_classe_id'); }
}