<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Annonce extends Model
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    public const TYPE_PUBLIC = 'public';
    public const TYPE_PRIVE  = 'prive';

    public const TYPES = [
        self::TYPE_PUBLIC,
        self::TYPE_PRIVE,
    ];

    public const TYPES_LIBELLES = [
        self::TYPE_PUBLIC => 'Publique',
        self::TYPE_PRIVE  => 'Privée',
    ];

    // ============================================================
    // CONFIGURATION
    // ============================================================

    protected $fillable = [
        'titre',
        'contenu',
        'type',
        'image',
        'date_debut',
        'date_fin',
        'est_active',
    ];

    protected $casts = [
        'date_debut' => 'datetime',
        'date_fin'   => 'datetime',
        'est_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'image_url',
        'type_libelle',
        'statut_libelle',
    ];

    // ============================================================
    // RELATIONS
    // ============================================================

    public function lecteurs(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'annonce_user')
            ->withPivot('lu_a')
            ->withTimestamps();
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

    public function scopeEnCours(Builder $query): Builder
    {
        $now = now();

        return $query->where('est_active', true)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('date_debut')->orWhere('date_debut', '<=', $now);
            })
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('date_fin')->orWhere('date_fin', '>=', $now);
            });
    }

    public function scopeExpirees(Builder $query): Builder
    {
        return $query->whereNotNull('date_fin')
            ->where('date_fin', '<', now());
    }

    public function scopeAVenir(Builder $query): Builder
    {
        return $query->whereNotNull('date_debut')
            ->where('date_debut', '>', now());
    }

    public function scopePubliques(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    public function scopePrivees(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PRIVE);
    }

    public function scopeType(Builder $query, ?string $type): Builder
    {
        return $type ? $query->where('type', $type) : $query;
    }

    public function scopeRecherche(Builder $query, ?string $recherche): Builder
    {
        $recherche = trim((string) $recherche);

        if ($recherche === '') {
            return $query;
        }

        return $query->where(function (Builder $q) use ($recherche): void {
            $q->where('titre', 'LIKE', "%{$recherche}%")
              ->orWhere('contenu', 'LIKE', "%{$recherche}%");
        });
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if ($this->image && file_exists(public_path('storage/' . $this->image))) {
                return asset('storage/' . $this->image);
            }

            return '';
        });
    }

    protected function typeLibelle(): Attribute
    {
        return Attribute::get(
            fn (): string => self::TYPES_LIBELLES[$this->type] ?? '—'
        );
    }

    protected function statutLibelle(): Attribute
    {
        return Attribute::get(function (): string {
            if (!$this->est_active) {
                return 'Désactivée';
            }

            if ($this->estExpiree()) {
                return 'Expirée';
            }

            if ($this->estAVenir()) {
                return 'À venir';
            }

            return 'En cours';
        });
    }

    // ============================================================
    // MÉTHODES MÉTIER
    // ============================================================

    public function estPublique(): bool
    {
        return $this->type === self::TYPE_PUBLIC;
    }

    public function estPrivee(): bool
    {
        return $this->type === self::TYPE_PRIVE;
    }

    public function estExpiree(): bool
    {
        return $this->date_fin !== null && $this->date_fin->isPast();
    }

    public function estAVenir(): bool
    {
        return $this->date_debut !== null && $this->date_debut->isFuture();
    }

    public function estEnCours(): bool
    {
        if (!$this->est_active) {
            return false;
        }

        if ($this->estExpiree() || $this->estAVenir()) {
            return false;
        }

        return true;
    }

    public function estLuePar(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->relationLoaded('lecteurs')) {
            return $this->lecteurs
                ->where('id', $user->id)
                ->whereNotNull('pivot.lu_a')
                ->isNotEmpty();
        }

        return $this->lecteurs()
            ->where('user_id', $user->id)
            ->whereNotNull('lu_a')
            ->exists();
    }

    public function marquerLuePar(User $user): void
    {
        $this->lecteurs()->syncWithoutDetaching([
            $user->id => ['lu_a' => now()],
        ]);
    }

    // ============================================================
    // HELPERS STATIQUES
    // ============================================================

    /**
     * Liste des types pour les <select>.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public static function typesPourSelect(): array
    {
        return collect(self::TYPES)
            ->map(fn (string $type): array => [
                'value' => $type,
                'label' => self::TYPES_LIBELLES[$type] ?? $type,
            ])
            ->all();
    }
}