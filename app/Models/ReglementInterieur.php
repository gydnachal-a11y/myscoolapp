<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReglementInterieur extends Model
{
    use HasFactory;

    // ============================================================
    // CONSTANTES
    // ============================================================

    public const CATEGORIE_REGLE         = 'regle';
    public const CATEGORIE_OBLIGATION    = 'obligation';
    public const CATEGORIE_INTERDICTION  = 'interdiction';

    public const CATEGORIES = [
        self::CATEGORIE_REGLE,
        self::CATEGORIE_OBLIGATION,
        self::CATEGORIE_INTERDICTION,
    ];

    public const CATEGORIES_LIBELLES = [
        self::CATEGORIE_REGLE        => 'Règle à suivre',
        self::CATEGORIE_OBLIGATION   => 'Obligation',
        self::CATEGORIE_INTERDICTION => 'Interdiction',
    ];

    public const ORDRE_DEFAUT = 9999;

    // ============================================================
    // CONFIGURATION
    // ============================================================

    /**
     * La migration a créé la table avec un "s" : "reglements_interieurs".
     * On le déclare explicitement car le pluriel de "reglement_interieur"
     * n'est pas déduit automatiquement par Eloquent.
     */
    protected $table = 'reglements_interieurs';

    protected $fillable = [
        'titre',
        'contenu',
        'categorie',
        'ordre',
        'est_actif',
    ];

    protected $casts = [
        'est_actif' => 'boolean',
        'ordre'     => 'integer',   // ✅ ajouté
        'created_at'=> 'datetime',
        'updated_at'=> 'datetime',
    ];

    protected $appends = [
        'categorie_label',
    ];

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeActif(Builder $query): Builder
    {
        return $query->where('est_actif', true);
    }

    public function scopeInactif(Builder $query): Builder
    {
        return $query->where('est_actif', false);
    }

    public function scopeCategorie(Builder $query, ?string $categorie): Builder
    {
        return $categorie ? $query->where('categorie', $categorie) : $query;
    }

    public function scopeStatut(Builder $query, ?string $statut): Builder
    {
        return match ($statut) {
            'actif'   => $query->where('est_actif', true),
            'inactif' => $query->where('est_actif', false),
            default   => $query,
        };
    }

    public function scopeRecherche(Builder $query, ?string $recherche): Builder
    {
        $recherche = trim((string) $recherche);

        if ($recherche === '') {
            return $query;
        }

        // ✅ Échappement des wildcards LIKE
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $recherche);

        return $query->where(function (Builder $q) use ($escaped): void {
            $q->where('titre', 'like', "%{$escaped}%")
              ->orWhere('contenu', 'like', "%{$escaped}%");
        });
    }

    /**
     * Tri par ordre croissant, en poussant les NULL à la fin.
     */
    public function scopeOrdonne(Builder $query): Builder
    {
        return $query
            ->orderByRaw('COALESCE(ordre, ?) ASC', [self::ORDRE_DEFAUT])
            ->orderBy('titre');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    protected function categorieLabel(): Attribute
    {
        return Attribute::get(
            fn (): string => self::CATEGORIES_LIBELLES[$this->categorie] ?? $this->categorie
        );
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function estActif(): bool
    {
        return (bool) $this->est_actif;
    }

    public function estRegle(): bool
    {
        return $this->categorie === self::CATEGORIE_REGLE;
    }

    public function estObligation(): bool
    {
        return $this->categorie === self::CATEGORIE_OBLIGATION;
    }

    public function estInterdiction(): bool
    {
        return $this->categorie === self::CATEGORIE_INTERDICTION;
    }

    public function basculerStatut(): self
    {
        $this->update(['est_actif' => ! $this->est_actif]);
        return $this;
    }

    // ============================================================
    // HELPERS STATIQUES
    // ============================================================

    /**
     * Liste des catégories pour les <select>.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function categoriesPourSelect(): array
    {
        return collect(self::CATEGORIES)
            ->map(fn (string $cat): array => [
                'value' => $cat,
                'label' => self::CATEGORIES_LIBELLES[$cat] ?? $cat,
            ])
            ->all();
    }

    /**
     * Icône FontAwesome associée à chaque catégorie.
     */
    public static function iconePourCategorie(string $categorie): string
    {
        return match ($categorie) {
            self::CATEGORIE_REGLE        => 'fa-list-check',
            self::CATEGORIE_OBLIGATION   => 'fa-circle-exclamation',
            self::CATEGORIE_INTERDICTION => 'fa-ban',
            default                      => 'fa-circle-info',
        };
    }

    /**
     * Couleur Tailwind (badge) associée à chaque catégorie.
     */
    public static function couleurPourCategorie(string $categorie): string
    {
        return match ($categorie) {
            self::CATEGORIE_REGLE        => 'blue',
            self::CATEGORIE_OBLIGATION   => 'amber',
            self::CATEGORIE_INTERDICTION => 'red',
            default                      => 'gray',
        };
    }
}