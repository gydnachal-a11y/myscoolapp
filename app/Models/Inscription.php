<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Throwable;

class Inscription extends Model
{
    // ==========================================
    // CONFIGURATION
    // ==========================================

    protected $fillable = [
        'eleve_id',
        'annee_scolaire_id',
        'salle_classe_id',
        'date_inscription',
        'reduction_frais',
        'frais_inscription_final',
        'frais_annuel_final',
    ];

    protected $casts = [
        'date_inscription'        => 'date',
        'reduction_frais'         => 'decimal:2',
        'frais_inscription_final' => 'decimal:2',
        'frais_annuel_final'      => 'decimal:2',
        'created_at'              => 'datetime',
        'updated_at'              => 'datetime',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class, 'annee_scolaire_id');
    }

    public function salleDeClasse(): BelongsTo
    {
        return $this->belongsTo(SalleDeClasse::class, 'salle_classe_id');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Date d'inscription formatée (jj/mm/aaaa).
     * Utilisable via $inscription->date_inscription_formatee
     * Robuste : gère string ET Carbon (si le cast n'a pas été appliqué).
     */
    protected function dateInscriptionFormatee(): Attribute
    {
        return Attribute::get(function (): string {
            $date = $this->date_inscription;

            if ($date === null) {
                return '—';
            }

            if (is_string($date)) {
                try {
                    $date = Carbon::parse($date);
                } catch (Throwable) {
                    return '—';
                }
            }

            return $date->format('d/m/Y');
        });
    }

    /**
     * Frais d'inscription formatés (avec séparateur de milliers).
     * Utilisable via $inscription->frais_inscription_formates
     */
    protected function fraisInscriptionFormates(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->frais_inscription_final !== null
                ? number_format((float) $this->frais_inscription_final, 2, ',', ' ') . ' USD'
                : '—'
        );
    }

    /**
     * Frais annuels formatés.
     */
    protected function fraisAnnuelFormates(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->frais_annuel_final !== null
                ? number_format((float) $this->frais_annuel_final, 2, ',', ' ') . ' USD'
                : '—'
        );
    }
}