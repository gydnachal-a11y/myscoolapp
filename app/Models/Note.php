<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    // ============================================================
    // CONSTANTES DE STATUT
    // ============================================================
    public const STATUT_BROUILLON = 'brouillon';
    public const STATUT_PUBLIE    = 'publie';

    protected $fillable = [
        'eleve_id',
        'cour_salle_id',
        'periode_note_id',
        'note',
        'appreciation',
        'saisie_par',
        'statut',
    ];

    protected $casts = [
        'note' => 'float',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function eleve(): BelongsTo
    {
        return $this->belongsTo(Eleve::class);
    }

    public function courSalle(): BelongsTo
    {
        return $this->belongsTo(CourSalle::class);
    }

    public function periodeNote(): BelongsTo
    {
        return $this->belongsTo(PeriodeNote::class);
    }

    public function saisiePar(): BelongsTo
    {
        return $this->belongsTo(User::class, 'saisie_par');
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopePourEleve(Builder $query, int $eleveId): Builder
    {
        return $query->where('eleve_id', $eleveId);
    }

    public function scopePourPeriode(Builder $query, int $periodeId): Builder
    {
        return $query->where('periode_note_id', $periodeId);
    }

    public function scopePourSalle(Builder $query, int $salleId): Builder
    {
        return $query->whereHas('courSalle.salle', fn ($q) => $q->where('id', $salleId));
    }

    public function scopePublie(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PUBLIE);
    }

    public function scopeBrouillon(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_BROUILLON);
    }

    /**
     * Filtre par statut (utilise les constantes).
     */
    public function scopeStatut(Builder $query, string $statut): Builder
    {
        return $query->where('statut', $statut);
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getNoteFormateeAttribute(): string
    {
        return $this->note !== null ? number_format($this->note, 2) : '—';
    }

    /**
     * Indique si la note est publiée.
     */
    public function getEstPublieeAttribute(): bool
    {
        return $this->statut === self::STATUT_PUBLIE;
    }
}