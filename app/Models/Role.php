<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class Role extends Model
{
    /*
    |--------------------------------------------------------------------------
    | CONSTANTES
    |--------------------------------------------------------------------------
    */

    /**
     * Rôles système protégés (ne peuvent être supprimés/modifiés).
     */
    public const PROTECTED_ROLES = ['super_admin', 'admin'];

    /**
     * Nombre maximal de tentatives de création d'un rôle en double.
     */
    private const MAX_DUPLICATE_ATTEMPTS = 3;

    /*
    |--------------------------------------------------------------------------
    | PROPRIÉTÉS
    |--------------------------------------------------------------------------
    */

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | BOOT
    |--------------------------------------------------------------------------
    */

    protected static function booted(): void
    {
        static::deleting(function (self $role) {
            $role->ensureNotProtected('supprimé');
        });

        static::updating(function (self $role) {
            $role->ensureNotProtected('modifié');
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Permissions attribuées à ce rôle.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /**
     * Utilisateurs ayant ce rôle.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'role_user');
    }

    /*
    |--------------------------------------------------------------------------
    | GESTION DES PERMISSIONS
    |--------------------------------------------------------------------------
    */

    /**
     * Vérifie si le rôle possède une permission donnée.
     */
    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * Vérifie si le rôle possède l'une des permissions données.
     */
    public function hasAnyPermission(array $permissionNames): bool
    {
        return $this->permissions()->whereIn('name', $permissionNames)->exists();
    }

    /**
     * Vérifie si le rôle possède toutes les permissions données.
     */
    public function hasAllPermissions(array $permissionNames): bool
    {
        if (empty($permissionNames)) {
            return true;
        }
        $count = $this->permissions()->whereIn('name', $permissionNames)->count();
        return $count === count($permissionNames);
    }

    /**
     * Donne une ou plusieurs permissions au rôle.
     */
    public function givePermissionTo(string|Permission|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $ids = [];

        foreach ($permissions as $perm) {
            if ($perm instanceof Permission) {
                $ids[] = $perm->id;
            } else {
                $found = Permission::where('name', $perm)->first();
                if ($found) {
                    $ids[] = $found->id;
                }
            }
        }

        if (!empty($ids)) {
            $this->permissions()->syncWithoutDetaching($ids);
        }

        return $this;
    }

    /**
     * Retire une ou plusieurs permissions du rôle.
     */
    public function revokePermissionTo(string|Permission|array $permissions): self
    {
        $permissions = is_array($permissions) ? $permissions : [$permissions];
        $ids = [];

        foreach ($permissions as $perm) {
            if ($perm instanceof Permission) {
                $ids[] = $perm->id;
            } else {
                $found = Permission::where('name', $perm)->first();
                if ($found) {
                    $ids[] = $found->id;
                }
            }
        }

        if (!empty($ids)) {
            $this->permissions()->detach($ids);
        }

        return $this;
    }

    /**
     * Synchronise les permissions du rôle (remplace toutes les permissions existantes).
     * Accepte un tableau de noms de permissions ou d'IDs.
     */
    public function syncPermissions(array $permissions): self
    {
        if (empty($permissions)) {
            $this->permissions()->detach();
            return $this;
        }

        // Détecter automatiquement si ce sont des IDs ou des noms
        $first = reset($permissions);
        if (is_int($first)) {
            $this->permissions()->sync($permissions);
        } else {
            $ids = Permission::whereIn('name', $permissions)->pluck('id')->toArray();
            $this->permissions()->sync($ids);
        }

        return $this;
    }

    /**
     * Récupère les noms de toutes les permissions du rôle.
     */
    public function getPermissionNamesAttribute(): array
    {
        return $this->permissions->pluck('name')->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /**
     * Filtrer par recherche (nom, label, description).
     */
    public function scopeSearch($query, ?string $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('label', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Filtrer les rôles qui ont au moins une des permissions données.
     */
    public function scopeWithPermissions($query, array $permissionNames)
    {
        return $query->whereHas('permissions', function ($q) use ($permissionNames) {
            $q->whereIn('name', $permissionNames);
        });
    }

    /**
     * Filtrer les rôles qui ont au moins un utilisateur.
     */
    public function scopeHasUsers($query)
    {
        return $query->has('users');
    }

    /**
     * Filtrer les rôles système (protégés).
     */
    public function scopeProtected($query)
    {
        return $query->whereIn('name', self::PROTECTED_ROLES);
    }

    /**
     * Filtrer les rôles non système (modifiables).
     */
    public function scopeModifiable($query)
    {
        return $query->whereNotIn('name', self::PROTECTED_ROLES);
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /**
     * Accesseur pour le libellé (fallback sur le nom si label vide).
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->label ?? ucfirst($this->name);
    }

    /**
     * Accesseur : alias 'label' pour compatibilité avec l'attribut existant.
     */
    public function getLabelAttribute($value): string
    {
        return $value ?? ucfirst($this->name);
    }

    /*
    |--------------------------------------------------------------------------
    | MÉTHODES UTILITAIRES
    |--------------------------------------------------------------------------
    */

    /**
     * Vérifie si ce rôle est protégé (système).
     */
    public function isProtected(): bool
    {
        return in_array($this->name, self::PROTECTED_ROLES);
    }

    /**
     * Vérifie si ce rôle est un rôle d'administration (alias).
     */
    public function isAdminRole(): bool
    {
        return $this->isProtected();
    }

    /**
     * Garantit que le rôle n'est pas protégé avant une opération.
     *
     * @throws \Exception
     */
    private function ensureNotProtected(string $action): void
    {
        if ($this->isProtected()) {
            $message = "Ce rôle système ne peut pas être {$action}.";
            Log::warning('Tentative de modification d\'un rôle protégé', [
                'role_id' => $this->id,
                'name' => $this->name,
                'action' => $action,
                'user_id' => auth()->id(),
            ]);
            throw new \Exception($message);
        }
    }

    /**
     * Compte le nombre de permissions associées.
     */
    public function getPermissionsCountAttribute(): int
    {
        return $this->permissions()->count();
    }

    /**
     * Compte le nombre d'utilisateurs associés.
     */
    public function getUsersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Crée un rôle avec ses permissions en une seule opération.
     */
    public static function createWithPermissions(array $roleData, array $permissionNames = []): self
    {
        $role = static::create($roleData);
        if (!empty($permissionNames)) {
            $role->syncPermissions($permissionNames);
        }
        return $role;
    }

    /**
     * Vérifie si le rôle a des utilisateurs associés.
     */
    public function hasUsers(): bool
    {
        return $this->users()->exists();
    }

    /**
     * Vérifie si le rôle a des permissions associées.
     */
    public function hasPermissions(): bool
    {
        return $this->permissions()->exists();
    }

    /**
     * Clone le rôle avec ses permissions (sans les utilisateurs).
     */
    public function duplicate(string $newName, ?string $newLabel = null): self
    {
        return DB::transaction(function () use ($newName, $newLabel) {
            $newRole = $this->replicate();
            $newRole->name = $newName;
            $newRole->label = $newLabel ?? $this->label . ' (copie)';
            $newRole->save();

            // Copier les permissions
            $newRole->permissions()->sync($this->permissions->pluck('id'));

            return $newRole;
        });
    }

    /**
     * Filtrer les IDs de rôles existants (utile pour les requêtes de validation).
     */
    public static function filterExistingIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        return self::whereIn('id', $ids)->pluck('id')->toArray();
    }
}