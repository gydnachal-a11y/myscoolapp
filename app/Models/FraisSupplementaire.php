<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class FraisSupplementaire extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'frais_supplementaires';

    protected $fillable = [
        'libelle',
        'montant',
        'date_debut',
        'date_fin',
        'description',
        'est_pour_toutes_salles',
        'est_ouvert',
    ];

    protected $casts = [
        'date_debut'             => 'date',
        'date_fin'               => 'date',
        'montant'                => 'decimal:2',
        'est_pour_toutes_salles' => 'boolean',
        'est_ouvert'             => 'boolean',
        'deleted_at'             => 'datetime',
    ];

    /**
     * Accessors exposés automatiquement en JSON / tableaux.
     */
    protected $appends = [
        'montant_formate',
        'periode_formatee',
        'statut_ouverture',
    ];

    /*
    |--------------------------------------------------------------------------
    | CONSTANTES
    |--------------------------------------------------------------------------
    */

    public const STATUT_OUVERT = 'Ouvert';
    public const STATUT_FERME  = 'Fermé';

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function salles(): BelongsToMany
    {
        return $this->belongsToMany(
            SalleDeClasse::class,
            'frais_supplementaire_salle',
            'frais_supplementaire_id',
            'salle_classe_id'
        );
    }

    public function paiements(): HasMany
    {
        return $this->hasMany(PaiementFraisSupplementaire::class);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopeOuverts(Builder $query): Builder
    {
        return $query->where('est_ouvert', true);
    }

    public function scopeFermes(Builder $query): Builder
    {
        return $query->where('est_ouvert', false);
    }

    public function scopePourToutesSalles(Builder $query): Builder
    {
        return $query->where('est_pour_toutes_salles', true);
    }

    public function scopePourSallesSpecifiques(Builder $query): Builder
    {
        return $query->where('est_pour_toutes_salles', false);
    }

    public function scopeEnCours(Builder $query): Builder
    {
        $today = Carbon::today();

        return $query->whereDate('date_debut', '<=', $today)
                     ->whereDate('date_fin', '>=', $today);
    }

    public function scopeExpires(Builder $query): Builder
    {
        return $query->whereDate('date_fin', '<', Carbon::today());
    }

    public function scopeAVenir(Builder $query): Builder
    {
        return $query->whereDate('date_debut', '>', Carbon::today());
    }

    /**
     * Recherche unifiée (libellé + description).
     */
    public function scopeRecherche(Builder $query, ?string $recherche): Builder
    {
        $recherche = trim((string) $recherche);

        if ($recherche === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($recherche): void {
            $q->where('libelle', 'LIKE', "%{$recherche}%")
              ->orWhere('description', 'LIKE', "%{$recherche}%");
        });
    }

    /**
     * Filtre par statut : 'ouvert' | 'ferme' | autre = pas de filtre.
     */
    public function scopeStatut(Builder $query, ?string $statut): Builder
    {
        return match ($statut) {
            'ouvert' => $query->where('est_ouvert', true),
            'ferme'  => $query->where('est_ouvert', false),
            default  => $query,
        };
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    protected function montantFormate(): Attribute
    {
        return Attribute::get(
            fn (): string => number_format((float) $this->montant, 2, ',', ' ')
        );
    }

    protected function periodeFormatee(): Attribute
    {
        return Attribute::get(function (): string {
            if (!$this->date_debut || !$this->date_fin) {
                return '—';
            }

            return $this->date_debut->format('d/m/Y')
                . ' → '
                . $this->date_fin->format('d/m/Y');
        });
    }

    protected function statutOuverture(): Attribute
    {
        return Attribute::get(
            fn (): string => $this->est_ouvert ? self::STATUT_OUVERT : self::STATUT_FERME
        );
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES MÉTIER — Null-safe
    |--------------------------------------------------------------------------
    */

    public function estOuvert(): bool
    {
        return (bool) $this->est_ouvert;
    }

    public function estFerme(): bool
    {
        return !$this->est_ouvert;
    }

    public function estEnCours(): bool
    {
        if (!$this->date_debut || !$this->date_fin) {
            return false;
        }

        $today = Carbon::today();

        return $this->date_debut->lte($today) && $this->date_fin->gte($today);
    }

    public function estExpire(): bool
    {
        if (!$this->date_fin) {
            return false;
        }

        return $this->date_fin->lt(Carbon::today());
    }

    public function estAVenir(): bool
    {
        if (!$this->date_debut) {
            return false;
        }

        return $this->date_debut->gt(Carbon::today());
    }

    /**
     * Vérifie si une salle est concernée (utilise la relation déjà chargée si possible).
     */
    public function concerneSalle(int $salleId): bool
    {
        if ($this->est_pour_toutes_salles) {
            return true;
        }

        // Utilise la collection chargée si elle existe → 0 requête
        if ($this->relationLoaded('salles')) {
            return $this->salles->contains('id', $salleId);
        }

        return $this->salles()->whereKey($salleId)->exists();
    }

    /**
     * Y a-t-il des paiements ? (utilise withCount si chargé)
     */
    public function aDesPaiements(): bool
    {
        if (array_key_exists('paiements_count', $this->attributes)) {
            return (int) $this->attributes['paiements_count'] > 0;
        }

        return $this->paiements()->exists();
    }

    /**
     * Nombre de paiements (utilise withCount si chargé).
     */
    public function nombreDePaiements(): int
    {
        if (array_key_exists('paiements_count', $this->attributes)) {
            return (int) $this->attributes['paiements_count'];
        }

        return $this->paiements()->count();
    }

    /**
     * Total collecté en USD (utilise withSum si chargé).
     */
    public function montantTotalCollecte(): float
    {
        if (array_key_exists('paiements_sum_montant_paye_usd', $this->attributes)) {
            return (float) $this->attributes['paiements_sum_montant_paye_usd'];
        }

        return (float) $this->paiements()->sum('montant_paye_usd');
    }

    public function montantTotalCollecteFormate(): string
    {
        return number_format($this->montantTotalCollecte(), 2, ',', ' ');
    }

    /**
     * Reste à collecter (montant unitaire - total collecté).
     */
    public function montantRestant(): float
    {
        return max(0.0, (float) $this->montant - $this->montantTotalCollecte());
    }

    public function montantRestantFormate(): string
    {
        return number_format($this->montantRestant(), 2, ',', ' ');
    }

    public function estTotalementPaye(): bool
    {
        return $this->montantTotalCollecte() >= (float) $this->montant;
    }

    /**
     * Salles concernées : toutes ou spécifiques.
     */
    public function getSallesConcernes(): Collection
    {
        if ($this->est_pour_toutes_salles) {
            return SalleDeClasse::query()
                ->orderBy('nom')
                ->get(['id', 'nom']);
        }

        return $this->salles;
    }

    /**
     * Paiements pour une salle donnée.
     */
    public function paiementsPourSalle(int $salleId): HasMany
    {
        return $this->paiements()->where('salle_classe_id', $salleId);
    }
}