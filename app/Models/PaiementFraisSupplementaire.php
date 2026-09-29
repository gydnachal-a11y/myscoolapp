<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaiementFraisSupplementaire extends Model
{
    use HasFactory;

    protected $fillable = [
        'eleve_id',
        'frais_supplementaire_id',
        'montant_paye_usd',
        'montant_paye_fc',
        'date_paiement',
        'commentaire',
    ];

    protected $casts = [
        'date_paiement' => 'date',
        'montant_paye_usd' => 'float',
        'montant_paye_fc' => 'float',
    ];

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function fraisSupplementaire(): BelongsTo
    {
        return $this->belongsTo(FraisSupplementaire::class);
    }
}