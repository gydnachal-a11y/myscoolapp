<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Période de notation (trimestre, semestre, etc.)
 *
 * @property int $id
 * @property int|null $annee_scolaire_id
 * @property string $nom
 * @property Carbon|null $date_debut
 * @property Carbon|null $date_fin
 * @property bool $est_active
 * @property string|null $description
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read AnneeScolaire|null $anneeScolaire
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Note> $notes
 */
class PeriodeNote extends Model
{
    protected $fillable = [
        'annee_scolaire_id',
        'nom',
        'date_debut',
        'date_fin',
        'est_active',
        'description',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin'   => 'date',
        'est_active' => 'boolean',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function anneeScolaire(): BelongsTo
    {
        return $this->belongsTo(AnneeScolaire::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('est_active', true);
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('est_active', false);
    }

    /**
     * Filtre les périodes d'une année scolaire donnée.
     */
    public function scopePourAnnee(Builder $query, int $anneeId): Builder
    {
        return $query->where('annee_scolaire_id', $anneeId);
    }

    /**
     * Filtre les périodes en cours (date du jour entre début et fin).
     * ✅ Utilise `today()` pour comparer correctement avec des colonnes `date`.
     */
    public function scopeEnCours(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '<=', today())
                     ->whereDate('date_fin', '>=', today());
    }

    public function scopeAVenir(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '>', today());
    }

    public function scopePassees(Builder $query): Builder
    {
        return $query->whereDate('date_fin', '<', today());
    }

    /**
     * Recherche par nom (LIKE %...%).
     */
    public function scopeRecherche(Builder $query, string $search): Builder
    {
        return $query->where('nom', 'LIKE', "%{$search}%");
    }

    /**
     * Tri chronologique décroissant.
     */
    public function scopeChronologique(Builder $query): Builder
    {
        return $query->orderByDesc('date_debut')->orderByDesc('id');
    }

    // ============================================================
    // MÉTHODES UTILITAIRES
    // ============================================================

    /**
     * Vérifie si la période est actuellement en cours.
     */
    public function estEnCours(): bool
    {
        if (!$this->date_debut || !$this->date_fin) {
            return false;
        }

        return today()->between($this->date_debut, $this->date_fin);
    }

    public function estAVenir(): bool
    {
        return $this->date_debut?->isFuture() ?? false;
    }

    public function estPassee(): bool
    {
        return $this->date_fin?->isPast() ?? false;
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * Période formatée (date_debut → date_fin).
     */
    public function getPeriodeFormateeAttribute(): string
    {
        if (!$this->date_debut || !$this->date_fin) {
            return '—';
        }

        return $this->date_debut->format('d/m/Y')
            . ' → '
            . $this->date_fin->format('d/m/Y');
    }

    /**
     * Statut en texte (Actif / Inactif / En cours / À venir / Passé).
     */
    public function getStatutTexteAttribute(): string
    {
        if (!$this->est_active) {
            return 'Inactive';
        }

        if ($this->estEnCours()) {
            return 'En cours';
        }

        if ($this->estAVenir()) {
            return 'À venir';
        }

        if ($this->estPassee()) {
            return 'Passée';
        }

        return 'Active';
    }

    /**
     * Clé CSS du statut (utile pour les badges).
     */
    public function getStatutKeyAttribute(): string
    {
        if (!$this->est_active) {
            return 'inactive';
        }

        return match (true) {
            $this->estEnCours() => 'en_cours',
            $this->estAVenir()  => 'a_venir',
            $this->estPassee()  => 'passee',
            default             => 'active',
        };
    }

    /**
     * Durée en jours.
     */
    public function getDureeJoursAttribute(): ?int
    {
        if (!$this->date_debut || !$this->date_fin) {
            return null;
        }

        return $this->date_debut->diffInDays($this->date_fin);
    }

    /**
     * Nombre de notes rattachées (compteur préchargé via withCount).
     */
    public function getNombreNotesAttribute(): int
    {
        return (int) ($this->notes_count ?? 0);
    }
}