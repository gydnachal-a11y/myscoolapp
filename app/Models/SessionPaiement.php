<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionPaiement extends Model
{
    protected $table = 'sessions_paiement';

    protected $fillable = [
        'salle_classe_id',
        'type_periode',
        'periode',
        'date_debut_session',
        'date_fin_session',
    ];

    protected $casts = [
        'date_debut_session' => 'date',
        'date_fin_session' => 'date',
    ];

    public function salleClasse(): BelongsTo
    {
        return $this->belongsTo(SalleDeClasse::class, 'salle_classe_id');
    }

    // Vérifie si la session est actuellement ouverte
    public function estOuverte(): bool
    {
        $now = now();
        return $now->between($this->date_debut_session, $this->date_fin_session);
    }

    // Vérifie si la session est expirée
    public function estExpiree(): bool
    {
        return now()->greaterThan($this->date_fin_session);
    }
}