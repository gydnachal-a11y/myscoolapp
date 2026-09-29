<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // ============================================================
    // CONSTANTES — RÔLES
    // ============================================================

    public const ROLE_SUPER_ADMIN = 'super_admin';
    public const ROLE_ADMIN       = 'admin';
    public const ROLE_DIRECTEUR   = 'directeur';
    public const ROLE_SECRETAIRE  = 'secretaire';
    public const ROLE_COMPTABLE   = 'comptable';
    public const ROLE_ENSEIGNANT  = 'enseignant';

    public const ROLES = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_DIRECTEUR,
        self::ROLE_SECRETAIRE,
        self::ROLE_COMPTABLE,
        self::ROLE_ENSEIGNANT,
    ];

    public const ROLES_ADMIN = [
        self::ROLE_SUPER_ADMIN,
        self::ROLE_ADMIN,
        self::ROLE_DIRECTEUR,
    ];

    public const ROLE_LABELS = [
        self::ROLE_SUPER_ADMIN => 'Super Administrateur',
        self::ROLE_ADMIN       => 'Administrateur',
        self::ROLE_DIRECTEUR   => 'Directeur',
        self::ROLE_SECRETAIRE  => 'Secrétaire',
        self::ROLE_COMPTABLE   => 'Comptable',
        self::ROLE_ENSEIGNANT  => 'Enseignant',
    ];

    // ============================================================
    // CONSTANTES — TYPES DE SALAIRE
    // ============================================================

    public const SALAIRE_FIXE        = 'fixe';
    public const SALAIRE_HORAIRE     = 'horaire';
    public const SALAIRE_MENSUEL     = 'mensuel';
    public const SALAIRE_MANUEL      = 'manuel';
    public const SALAIRE_AUTOMATIQUE = 'automatique';

    public const SALAIRE_TYPES = [
        self::SALAIRE_FIXE,
        self::SALAIRE_HORAIRE,
        self::SALAIRE_MENSUEL,
        self::SALAIRE_MANUEL,
        self::SALAIRE_AUTOMATIQUE,
    ];

    public const SALAIRE_TYPES_AUTO = [
        self::SALAIRE_AUTOMATIQUE,
        self::SALAIRE_FIXE,
        self::SALAIRE_HORAIRE,
        self::SALAIRE_MENSUEL,
    ];

    // ============================================================
    // CONSTANTES — DIVERS
    // ============================================================

    private const MATRICULE_PREFIX       = 'EMP';
    private const MATRICULE_MAX_ATTEMPTS = 10;
    private const CACHE_TTL_PERMISSIONS  = 3600;
    private const CACHE_TTL_PHOTO_URL    = 300;
    private const DEFAULT_AVATAR         = 'images/default-avatar.png';

    /** Clés de cache (centralisées). */
    private const CACHE_PREFIX_PERMISSIONS = 'user_all_permissions_';

    public const LOGIN_FIELDS = ['email', 'telephone'];

    // ============================================================
    // ATTRIBUTS
    // ============================================================

    protected $fillable = [
        'name', 'email', 'password', 'role', 'sexe', 'adresse', 'telephone',
        'date_naissance', 'matricule', 'photo',
        'type_salaire', 'salaire_mensuel_usd', 'salaire_mensuel_fc',
        'salaire_auto_base_usd', 'salaire_auto_base_fc',
        'salaire_ajuste_usd', 'salaire_ajuste_fc',
        'date_fixation_salaire', 'fonction_id', 'section_id', 'contact_id',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at'     => 'datetime',
        'date_naissance'        => 'date',
        'date_fixation_salaire' => 'date',
        'salaire_mensuel_usd'   => 'decimal:2',
        'salaire_mensuel_fc'    => 'decimal:2',
        'salaire_auto_base_usd' => 'decimal:2',
        'salaire_auto_base_fc'  => 'decimal:2',
        'salaire_ajuste_usd'    => 'decimal:2',
        'salaire_ajuste_fc'     => 'decimal:2',
        'contact_id'            => 'integer',
    ];

    protected $appends = [
        'role_label',
        'photo_url',
        'salaire_actuel_usd',
        'salaire_actuel_fc',
        'can_switch_to_contact',
    ];

    // ============================================================
    // ✅ NOUVEAU — MEMOIZATION RUNTIME
    // ============================================================

    /**
     * Cache runtime (durée de la requête PHP uniquement).
     *
     * ✅ Évite les appels répétés à `getAllPermissionNames()` dans la même
     *    requête (layout + middleware + controllers/policies).
     *
     * ⚠️ Reset automatique à chaque nouvelle requête PHP-FPM.
     *    Pour Octane/Swoole, ce cache est reset via `clearPermissionCache()`.
     */
    private ?array $runtimePermissionCache = null;

    // ============================================================
    // RELATIONS
    // ============================================================

    public function fonction(): BelongsTo
    {
        return $this->belongsTo(Fonction::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function coursEnseignes(): HasMany
    {
        return $this->hasMany(CourSalle::class, 'titulaire_id');
    }

    public function paiementSalaires(): HasMany
    {
        return $this->hasMany(PaiementSalaire::class, 'user_id');
    }

    public function avances(): HasMany
    {
        return $this->hasMany(AvanceSalaire::class, 'user_id');
    }

    public function demandesAvance(): HasMany
    {
        return $this->hasMany(DemandeAvance::class, 'user_id');
    }

    public function demandesTraitees(): HasMany
    {
        return $this->hasMany(DemandeAvance::class, 'traite_par');
    }

    public function sessionsAvanceCreees(): HasMany
    {
        return $this->hasMany(SessionAvance::class, 'created_by');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    // ============================================================
    // HELPERS CONTACT
    // ============================================================

    public function hasContactAccount(): bool
    {
        return $this->contact_id !== null;
    }

    public function linkedContact(): ?Contact
    {
        if (! $this->hasContactAccount()) {
            return null;
        }

        return $this->relationLoaded('contact')
            ? $this->contact
            : $this->contact()->first();
    }

    // ============================================================
    // ✅ VÉRIFICATION DES RÔLES — AMÉLIORÉE
    // ============================================================

    /**
     * ✅ CORRECTION #1 : vérifie la colonne `role` ET le pivot `roles`.
     *
     * Avant : seul `$this->role === 'super_admin'` était testé.
     * Si un user avait super_admin uniquement via le pivot, le bypass échouait.
     */
    public function isSuperAdmin(): bool
    {
        if ($this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Fallback pivot : si role = null mais pivot = super_admin
        // ⚠️ `hasRole()` vérifie déjà `relationLoaded` pour éviter le N+1
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', self::ROLE_SUPER_ADMIN);
        }

        return false;
    }

    /**
     * ✅ AMÉLIORÉ : vérifie colonne + pivot (comme isSuperAdmin).
     */
    public function isAdmin(): bool
    {
        if (in_array($this->role, self::ROLES_ADMIN, true)) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', self::ROLES_ADMIN)->isNotEmpty();
        }

        return false;
    }

    public function isDirecteur(): bool
    {
        return $this->role === self::ROLE_DIRECTEUR;
    }

    public function isSecretaire(): bool
    {
        return $this->role === self::ROLE_SECRETAIRE;
    }

    public function isComptable(): bool
    {
        return $this->role === self::ROLE_COMPTABLE;
    }

    public function isEnseignant(): bool
    {
        return $this->role === self::ROLE_ENSEIGNANT;
    }

    /**
     * ✅ NOUVEAU : inverse de isAdmin() — utile pour les guards.
     */
    public function isMember(): bool
    {
        return ! $this->isAdmin();
    }

    /**
     * ✅ NOUVEAU : l'utilisateur a-t-il vérifié son email ?
     */
    public function isActive(): bool
    {
        return $this->email_verified_at !== null;
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = is_string($roles) ? [$roles] : $roles;

        if (in_array($this->role, $roles, true)) {
            return true;
        }

        if ($this->relationLoaded('roles')) {
            return $this->roles->whereIn('name', $roles)->isNotEmpty();
        }

        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    public function hasAllRoles(array $roles): bool
    {
        if (empty($roles)) {
            return true;
        }

        $userRoles = $this->relationLoaded('roles')
            ? $this->roles->pluck('name')->all()
            : $this->roles()->pluck('name')->all();

        if ($this->role !== null && ! in_array($this->role, $userRoles, true)) {
            $userRoles[] = $this->role;
        }

        return empty(array_diff($roles, $userRoles));
    }

    // ============================================================
    // ✅ PERMISSIONS — VERSION ROBUSTE ENRICHIE
    // ============================================================

    /**
     * Récupère TOUTES les permissions de l'utilisateur (noms uniques).
     *
     * ✅ Retourne un ARRAY de strings (sérialisation fiable).
     * ✅ Cache persistant (1h) + mémoization runtime.
     *
     * @return array<int, string>
     */
    public function getAllPermissionNames(): array
    {
        if ($this->id === null) {
            return [];
        }

        // ✅ Memoization runtime (le plus rapide)
        if ($this->runtimePermissionCache !== null) {
            return $this->runtimePermissionCache;
        }

        $cacheKey = self::CACHE_PREFIX_PERMISSIONS . $this->id;

        // 1. Cache persistant (avec garde-fou sérialisation)
        try {
            $cached = Cache::get($cacheKey);

            if (is_array($cached)) {
                $this->runtimePermissionCache = $cached;
                return $cached;
            }

            if ($cached !== null) {
                Log::warning('Cache permissions corrompu — reconstruction', [
                    'user_id' => $this->id,
                    'type'    => gettype($cached),
                    'class'   => is_object($cached) ? get_class($cached) : null,
                ]);

                Cache::forget($cacheKey);
            }
        } catch (Throwable $e) {
            Log::warning('Erreur lecture cache permissions — reset', [
                'user_id' => $this->id,
                'error'   => $e->getMessage(),
            ]);

            Cache::forget($cacheKey);
        }

        // 2. Reconstruction
        $permissions = $this->computeAllPermissionNames();
        $this->runtimePermissionCache = $permissions;

        // 3. Mise en cache
        try {
            Cache::put($cacheKey, $permissions, self::CACHE_TTL_PERMISSIONS);
        } catch (Throwable $e) {
            Log::warning('Impossible de mettre les permissions en cache', [
                'user_id' => $this->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return $permissions;
    }

    /**
     * Calcule les permissions depuis la DB (sans cache).
     *
     * @return array<int, string>
     */
    private function computeAllPermissionNames(): array
    {
        try {
            /* ── 1. Permissions directes ── */
            $direct = $this->permissions()
                ->pluck('permissions.name')
                ->all();

            /* ── 2. IDs des rôles many-to-many ── */
            $roleIds = $this->roles()->pluck('roles.id')->all();

            /* ── 3. Ajout du rôle principal (colonne `role`) ── */
            if ($this->role) {
                $primaryRoleId = Role::query()
                    ->where('name', $this->role)
                    ->value('id');

                if ($primaryRoleId && ! in_array($primaryRoleId, $roleIds, true)) {
                    $roleIds[] = $primaryRoleId;
                }
            }

            /* ── 4. Permissions via tous les rôles ── */
            $viaRoles = empty($roleIds)
                ? []
                : Permission::query()
                    ->whereHas('roles', fn (Builder $q) => $q->whereIn('roles.id', $roleIds))
                    ->pluck('permissions.name')
                    ->all();

            /* ── 5. Union unique ── */
            return array_values(array_unique(array_merge($direct, $viaRoles)));

        } catch (Throwable $e) {
            Log::error('Erreur calcul permissions user', [
                'user_id' => $this->id,
                'error'   => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Vérifie si l'utilisateur possède une permission donnée.
     *
     * ✅ Super admin → bypass total
     * ✅ Memoization runtime via getAllPermissionNames()
     */
    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return in_array($permissionName, $this->getAllPermissionNames(), true);
    }

    /**
     * ✅ NOUVEAU : vérifie si l'utilisateur a AU MOINS UNE des permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        if (empty($permissions)) {
            return false;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $userPerms = $this->getAllPermissionNames();
        $lookup = array_flip($userPerms); // ✅ O(1) par lookup

        foreach ($permissions as $perm) {
            if (isset($lookup[$perm])) {
                return true;
            }
        }

        return false;
    }

    /**
     * ✅ NOUVEAU : vérifie si l'utilisateur a TOUTES les permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        if (empty($permissions)) {
            return true;
        }

        if ($this->isSuperAdmin()) {
            return true;
        }

        $userPerms = $this->getAllPermissionNames();
        $lookup = array_flip($userPerms);

        foreach ($permissions as $perm) {
            if (! isset($lookup[$perm])) {
                return false;
            }
        }

        return true;
    }

    /**
     * ✅ NOUVEAU : détecte si l'user doit voir le menu admin.
     *
     * Combine :
     *   - rôle admin (colonne ou pivot)
     *   - présence d'au moins une permission admin.*
     *
     * Utilisé par le layout pour décider admin vs membre.
     */
    public function canAccessAdminPanel(): bool
    {
        if ($this->isAdmin() || $this->isSuperAdmin()) {
            return true;
        }

        // Au moins une permission non-membre
        foreach ($this->getAllPermissionNames() as $perm) {
            if (! str_starts_with($perm, 'member.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie une permission par motif LIKE (avec wildcard %).
     * Exemple : hasPermissionLike('admin.%')
     */
    public function hasPermissionLike(string $pattern): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $regex = '/^' . str_replace('%', '.*', preg_quote($pattern, '/')) . '$/';

        foreach ($this->getAllPermissionNames() as $name) {
            if (preg_match($regex, $name) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie une permission DIRECTE (sans passer par les rôles).
     */
    public function hasDirectPermission(string $permissionName): bool
    {
        if ($this->relationLoaded('permissions')) {
            return $this->permissions->where('name', $permissionName)->isNotEmpty();
        }

        return $this->permissions()->where('name', $permissionName)->exists();
    }

    /**
     * ✅ NOUVEAU : alias retournant une Collection (compatibilité).
     */
    public function getAllPermissions(): Collection
    {
        return collect($this->getAllPermissionNames());
    }

    // ============================================================
    // GESTION DES PERMISSIONS / RÔLES
    // ============================================================

    public function givePermissionTo(string|Permission $permission): void
    {
        $perm = $permission instanceof Permission
            ? $permission
            : Permission::where('name', $permission)->firstOrFail();

        if (! $this->hasDirectPermission($perm->name)) {
            $this->permissions()->attach($perm->id);
            $this->clearPermissionCache();
        }
    }

    public function revokePermissionTo(string|Permission $permission): void
    {
        $perm = $permission instanceof Permission
            ? $permission
            : Permission::where('name', $permission)->firstOrFail();

        $this->permissions()->detach($perm->id);
        $this->clearPermissionCache();
    }

    public function syncPermissions(array $permissions): void
    {
        $permIds = Permission::whereIn('name', $permissions)->pluck('id')->all();
        $this->permissions()->sync($permIds);
        $this->clearPermissionCache();
    }

    /**
     * ✅ Synchronise les rôles many-to-many ET met à jour
     *    la colonne `role` avec le premier rôle (rôle principal).
     */
    public function syncRoles(array $roleNames): void
    {
        $roleIds = Role::whereIn('name', $roleNames)->pluck('id')->all();

        $this->roles()->sync($roleIds);

        if (! empty($roleNames)) {
            $this->update(['role' => $roleNames[0]]);
        }

        $this->clearPermissionCache();
    }

    /**
     * ✅ Vide TOUS les caches (persistant + runtime) de cet utilisateur.
     */
    public function clearPermissionCache(): void
    {
        // Runtime
        $this->runtimePermissionCache = null;

        if ($this->id === null) {
            return;
        }

        // Persistant
        Cache::forget(self::CACHE_PREFIX_PERMISSIONS . $this->id);
        Cache::forget("user_roles_{$this->id}");
        Cache::forget("user_permissions_{$this->id}");
    }

    /**
     * ✅ NOUVEAU : alias explicite (plus lisible dans certains contextes).
     */
    public function forgetPermissionCache(): void
    {
        $this->clearPermissionCache();
    }

    /**
     * ✅ NOUVEAU : force le recalcul depuis la DB (debug / après migration).
     */
    public function refreshPermissionCache(): array
    {
        $this->clearPermissionCache();
        return $this->getAllPermissionNames();
    }

    // ============================================================
    // MUTATEURS
    // ============================================================

    public function setPasswordAttribute(?string $value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($value)
            ? Hash::make($value)
            : $value;
    }

    public function setEmailAttribute(string $value): void
    {
        $this->attributes['email'] = Str::lower(trim($value));
    }

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = trim($value);
    }

    public function setTelephoneAttribute(?string $value): void
    {
        $this->attributes['telephone'] = $value
            ? preg_replace('/[^0-9+]/', '', $value)
            : null;
    }

    public function setMatriculeAttribute(?string $value): void
    {
        $this->attributes['matricule'] = $value
            ? Str::upper(trim($value))
            : null;
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getRoleLabelAttribute(): string
    {
        if ($this->role === null) {
            return 'Membre';
        }

        return self::ROLE_LABELS[$this->role]
            ?? Str::headline(str_replace('_', ' ', $this->role));
    }

    public function getPhotoUrlAttribute(): string
    {
        if (empty($this->photo)) {
            return asset(self::DEFAULT_AVATAR);
        }

        $cacheKey = "user_photo_url_{$this->id}_{$this->photo}";

        return Cache::remember($cacheKey, self::CACHE_TTL_PHOTO_URL, function (): string {
            try {
                if (Storage::disk('public')->exists($this->photo)) {
                    return Storage::disk('public')->url($this->photo);
                }
            } catch (Throwable $e) {
                // silence
            }

            return asset(self::DEFAULT_AVATAR);
        });
    }

    public function getSalaireActuelUsdAttribute(): float
    {
        if ($this->type_salaire === self::SALAIRE_MANUEL) {
            return (float) ($this->salaire_mensuel_usd ?? 0);
        }

        return (float) ($this->salaire_ajuste_usd ?? $this->salaire_auto_base_usd ?? 0);
    }

    public function getSalaireActuelFcAttribute(): float
    {
        if ($this->type_salaire === self::SALAIRE_MANUEL) {
            return (float) ($this->salaire_mensuel_fc ?? 0);
        }

        return (float) ($this->salaire_ajuste_fc ?? $this->salaire_auto_base_fc ?? 0);
    }

    protected function canSwitchToContact(): Attribute
    {
        return Attribute::get(fn (): bool => $this->contact_id !== null);
    }

    // ============================================================
    // HELPERS SALAIRE
    // ============================================================

    public function isSalaireFixe(): bool        { return $this->type_salaire === self::SALAIRE_FIXE; }
    public function isSalaireHoraire(): bool     { return $this->type_salaire === self::SALAIRE_HORAIRE; }
    public function isSalaireMensuel(): bool     { return $this->type_salaire === self::SALAIRE_MENSUEL; }
    public function isSalaireManuel(): bool      { return $this->type_salaire === self::SALAIRE_MANUEL; }
    public function isSalaireAutomatique(): bool { return $this->type_salaire === self::SALAIRE_AUTOMATIQUE; }

    public function getTypeSalaireLabelAttribute(): string
    {
        return match ($this->type_salaire) {
            self::SALAIRE_FIXE        => 'Fixe',
            self::SALAIRE_HORAIRE     => 'Horaire',
            self::SALAIRE_MENSUEL     => 'Mensuel',
            self::SALAIRE_MANUEL      => 'Manuel',
            self::SALAIRE_AUTOMATIQUE => 'Automatique',
            default                   => '—',
        };
    }

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeOfRole(Builder $query, string|array|null $roles = null): Builder
    {
        if (empty($roles)) {
            return $query;
        }

        return $query->whereIn('role', is_string($roles) ? [$roles] : $roles);
    }

    public function scopeWithRole(Builder $query, string|array|null $roleNames = null): Builder
    {
        if (empty($roleNames)) {
            return $query;
        }

        return $query->whereHas(
            'roles',
            fn (Builder $q) => $q->whereIn('name', is_string($roleNames) ? [$roleNames] : $roleNames)
        );
    }

    public function scopeEnseignants(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('role', self::ROLE_ENSEIGNANT)
              ->orWhereHas('coursEnseignes');
        });
    }

    public function scopeAdmins(Builder $query): Builder
    {
        return $query->whereIn('role', [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]);
    }

    public function scopeSearch(Builder $query, ?string $search = null): Builder
    {
        if (empty($search)) {
            return $query;
        }

        $search = trim($search);

        return $query->where(function (Builder $q) use ($search) {
            $q->where('name',      'LIKE', "%{$search}%")
              ->orWhere('email',     'LIKE', "%{$search}%")
              ->orWhere('matricule', 'LIKE', "%{$search}%")
              ->orWhere('telephone', 'LIKE', "%{$search}%");
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotNull('email_verified_at');
    }

    public function scopeWithSalaireManuel(Builder $query): Builder
    {
        return $query->where('type_salaire', self::SALAIRE_MANUEL);
    }

    public function scopeWithSalaireAutomatique(Builder $query): Builder
    {
        return $query->whereIn('type_salaire', self::SALAIRE_TYPES_AUTO);
    }

    public function scopeWithContactAccount(Builder $query): Builder
    {
        return $query->whereNotNull('contact_id');
    }

    public function scopeWithoutContactAccount(Builder $query): Builder
    {
        return $query->whereNull('contact_id');
    }

    // ============================================================
    // MÉTHODES UTILITAIRES
    // ============================================================

    public function canBeAssignedRole(string $role): bool
    {
        return in_array($role, self::ROLES, true);
    }

    public function attachContact(?int $contactId): self
    {
        $this->contact_id = $contactId;
        $this->save();

        return $this;
    }

    public static function generateUniqueMatricule(): string
    {
        $year   = date('Y');
        $prefix = self::MATRICULE_PREFIX;

        for ($attempt = 0; $attempt < self::MATRICULE_MAX_ATTEMPTS; $attempt++) {
            try {
                $matricule = self::generateMatriculeCandidate($year, $prefix);

                if (! self::where('matricule', $matricule)->exists()) {
                    return $matricule;
                }
            } catch (Throwable $e) {
                usleep(50_000);
            }
        }

        throw new RuntimeException(
            "Impossible de générer un matricule unique après "
            . self::MATRICULE_MAX_ATTEMPTS . " tentatives."
        );
    }

    private static function generateMatriculeCandidate(string $year, string $prefix): string
    {
        return DB::transaction(function () use ($year, $prefix) {
            $last = self::where('matricule', 'LIKE', $prefix . '-' . $year . '-%')
                ->orderByDesc('matricule')
                ->lockForUpdate()
                ->value('matricule');

            $lastNumber = 0;

            if ($last !== null) {
                $parts = explode('-', $last);
                $lastNumber = (int) end($parts);
            }

            return $prefix . '-' . $year . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        });
    }

    // ============================================================
    // BOOT — ÉVÉNEMENTS
    // ============================================================

    protected static function booted(): void
    {
        $invalidate = function (self $user): void {
            if ($user->id === null) {
                return;
            }

            Cache::forget('dettes_actives_user_' . $user->id);
            Cache::forget('session_avance_active');

            // ✅ Vide tous les caches de permissions (persistant + runtime)
            $user->clearPermissionCache();

            Cache::forget("user_photo_url_{$user->id}_" . ($user->getOriginal('photo') ?? ''));
            Cache::forget("user_photo_url_{$user->id}_" . ($user->photo ?? ''));
        };

        static::saved($invalidate);
        static::deleted($invalidate);

        static::updated(function (self $user): void {
            $dirty = $user->getDirty();

            if (array_key_exists('role', $dirty)) {
                $user->clearPermissionCache();
            }

            if (array_key_exists('contact_id', $dirty)) {
                $oldContactId = $user->getOriginal('contact_id');
                $newContactId = $user->contact_id;

                foreach ([$oldContactId, $newContactId] as $contactId) {
                    if ($contactId) {
                        Cache::forget("contact_{$contactId}_unread_messages");
                    }
                }
            }
        });
    }
}