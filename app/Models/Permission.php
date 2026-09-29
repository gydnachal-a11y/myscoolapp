<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission
 *
 * @property int $id
 * @property string $name              Ex: "paiements.index"
 * @property string|null $label        Libellé lisible
 * @property string|null $description
 * @property string|null $resource     Ex: "paiements" (extrait automatiquement)
 * @property string|null $action       Ex: "index" (extrait automatiquement)
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Role> $roles
 */
class Permission extends Model
{
    /**
     * Les attributs assignables en masse.
     */
    protected $fillable = [
        'name',
        'label',
        'description',
        'resource',
        'action',
    ];

    /**
     * Les attributs à caster.
     */
    protected $casts = [
        'resource' => 'string',
        'action' => 'string',
    ];

    // ==========================================
    // Relations
    // ==========================================

    /**
     * Les rôles qui possèdent cette permission.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    // ==========================================
    // Accesseurs & Mutateurs
    // ==========================================

    /**
     * Accesseur pour le champ 'resource' : si non défini, extrait depuis le name.
     */
    public function getResourceAttribute(?string $value): ?string
    {
        if ($value !== null) {
            return $value;
        }
        return $this->extractResourceFromName();
    }

    /**
     * Accesseur pour le champ 'action' : si non défini, extrait depuis le name.
     */
    public function getActionAttribute(?string $value): ?string
    {
        if ($value !== null) {
            return $value;
        }
        return $this->extractActionFromName();
    }

    /**
     * Extrait la ressource du nom (partie avant le premier point).
     */
    private function extractResourceFromName(): ?string
    {
        $parts = explode('.', $this->name);
        return $parts[0] ?? null;
    }

    /**
     * Extrait l'action du nom (partie après le premier point).
     */
    private function extractActionFromName(): ?string
    {
        $parts = explode('.', $this->name);
        return $parts[1] ?? null;
    }

    // ==========================================
    // Scopes
    // ==========================================

    /**
     * Filtre les permissions par ressource.
     */
    public function scopeByResource($query, string $resource)
    {
        return $query->where('resource', $resource)
            ->orWhere('name', 'LIKE', $resource . '.%');
    }

    /**
     * Filtre les permissions par action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action)
            ->orWhere('name', 'LIKE', '%.' . $action);
    }

    /**
     * Filtre les permissions appartenant à une liste de ressources.
     */
    public function scopeWhereResourceIn($query, array $resources)
    {
        return $query->whereIn('resource', $resources)
            ->orWhere(function ($q) use ($resources) {
                foreach ($resources as $resource) {
                    $q->orWhere('name', 'LIKE', $resource . '.%');
                }
            });
    }

    // ==========================================
    // Méthodes utilitaires
    // ==========================================

    /**
     * Vérifie si la permission est une action sur une ressource.
     */
    public function isActionOnResource(): bool
    {
        return str_contains($this->name, '.');
    }

    /**
     * Retourne le libellé par défaut (label ou nom formaté).
     */
    public function getDefaultLabel(): string
    {
        return $this->label ?? ucwords(str_replace(['.', '_', '-'], ' ', $this->name));
    }
}