<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class RoleController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const PER_PAGE              = 15;
    private const PER_PAGE_MAX          = 100;
    private const NAME_MAX              = 255;
    private const DESCRIPTION_MAX       = 500;

    private const PROTECTED_ROLES       = ['super_admin'];
    private const AUTO_ADMIN_ROLE       = 'admin';
    private const DEFAULT_FALLBACK_ROLE = 'secretaire';

    /** ✅ Cache global des permissions groupées (aligné avec PermissionController + SyncPermissions). */
    public const CACHE_PERMISSIONS      = 'permissions_grouped_v1';
    public const CACHE_RESOURCES        = 'permissions_resources_v1';
    private const CACHE_TTL             = 600;

    /** ✅ Préfixe du cache par utilisateur (aligné avec User::getAllPermissionNames). */
    private const CACHE_USER_PERMS_PREFIX = 'user_all_permissions_';

    /** Préfixes des scopes. */
    private const SCOPE_ADMIN  = 'admin';
    private const SCOPE_MEMBER = 'member';

    private const SCOPE_MAP = [
        'member.' => self::SCOPE_MEMBER,
        'admin.'  => self::SCOPE_ADMIN,
    ];

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        $request->validate([
            'search'   => ['nullable', 'string', 'max:100'],
            'type'     => ['nullable', 'in:system,custom'],
            'sort'     => ['nullable', 'in:name_asc,name_desc,permissions_desc,users_desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:' . self::PER_PAGE_MAX],
        ]);

        $search      = trim((string) $request->input('search', ''));
        $type        = $request->input('type');
        $sort        = $request->input('sort', 'name_asc');
        $systemRoles = $this->systemRoles();

        $query = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name',  'LIKE', "%{$search}%")
                        ->orWhere('label', 'LIKE', "%{$search}%");
                });
            })
            ->when($type === 'system', fn ($q) => $q->whereIn('name', $systemRoles))
            ->when($type === 'custom', fn ($q) => $q->whereNotIn('name', $systemRoles));

        match ($sort) {
            'name_desc'        => $query->orderByDesc('name'),
            'permissions_desc' => $query->orderByDesc('permissions_count'),
            'users_desc'       => $query->orderByDesc('users_count'),
            default            => $query->orderBy('name'),
        };

        $roles = $query
            ->paginate((int) $request->input('per_page', self::PER_PAGE))
            ->withQueryString();

        return view('admin.roles.index', compact('roles', 'search', 'type', 'sort'));
    }

    // ============================================================
    // SHOW
    // ============================================================

    public function show(Role $role): View
    {
        $role->loadCount(['permissions', 'users'])
             ->load([
                 'permissions:id,name,label,resource,action',
                 'users:id,name,email,role',
             ]);

        return view('admin.roles.show', compact('role'));
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    public function create(): View
    {
        $permissions = $this->getPermissionsGrouped();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateRole($request);

        try {
            $role = DB::transaction(function () use ($data, $request): Role {
                $role = Role::create([
                    'name'        => $data['name'],
                    'label'       => $data['label'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                $this->syncRolePermissions($role, $request->input('permissions', []));

                return $role;
            });

            $this->flushPermissionsCache();

            Log::info('Rôle créé', [
                'role_id'     => $role->id,
                'name'        => $role->name,
                'permissions' => count($data['permissions'] ?? []),
                'actor_id'    => auth()->id(),
            ]);

            return redirect()
                ->route('admin.roles.index')
                ->with('success', "Rôle « {$role->name} » créé avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur création rôle', [
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création.');
        }
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    public function edit(Role $role): View|RedirectResponse
    {
        if ($this->isProtectedRole($role)) {
            return redirect()
                ->route('admin.roles.index')
                ->with('error', "Le rôle système « {$role->name} » ne peut pas être modifié.");
        }

        $permissions     = $this->getPermissionsGrouped();
        $rolePermissions = $this->rolePermissionIds($role);

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        if ($this->isProtectedRole($role)) {
            return back()->with('error', "Le rôle système « {$role->name} » ne peut pas être modifié.");
        }

        $data = $this->validateRole($request, $role->id);

        // ✅ Capture les users AVANT la modification pour invalider leur cache
        $affectedUserIds = $role->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        try {
            DB::transaction(function () use ($role, $data, $request): void {
                $role->update([
                    'name'        => $data['name'],
                    'label'       => $data['label'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                $this->syncRolePermissions($role, $request->input('permissions', []));
            });

            $this->flushPermissionsCache();

            // ✅ Invalide le cache de tous les users impactés
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Rôle mis à jour', [
                'role_id'        => $role->id,
                'name'           => $role->name,
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.roles.index')
                ->with('success', "Rôle « {$role->name} » mis à jour avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur mise à jour rôle', [
                'role_id'  => $role->id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Role $role): RedirectResponse
    {
        if ($this->isProtectedRole($role)) {
            return back()->with('error', "Le rôle système « {$role->name} » ne peut pas être supprimé.");
        }

        $roleName = $role->name;
        $roleId   = $role->id;

        // ✅ Capture les users AVANT la suppression (au cas où la FK cascade ne soit pas gérée)
        $affectedUserIds = $role->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        try {
            DB::transaction(function () use ($role): void {
                $usersCount = DB::table('role_user')
                    ->where('role_id', $role->id)
                    ->lockForUpdate()
                    ->count();

                if ($usersCount > 0) {
                    $plural = $usersCount > 1 ? 's' : '';
                    throw new \RuntimeException(
                        "Ce rôle est attribué à {$usersCount} utilisateur{$plural}. "
                        . "Retirez-le d'abord de ces utilisateurs."
                    );
                }

                $role->permissions()->detach();
                $role->delete();
            });

            $this->flushPermissionsCache();
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Rôle supprimé', [
                'role_id'        => $roleId,
                'name'           => $roleName,
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.roles.index')
                ->with('success', "Rôle « {$roleName} » supprimé avec succès.");

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('Erreur suppression rôle', [
                'role_id'  => $roleId,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->with('error', 'Impossible de supprimer ce rôle.');
        }
    }

    // ============================================================
    // ASSIGNATION UTILISATEURS
    // ============================================================

    public function assignUsers(Role $role): View
    {
        $search  = trim((string) request('search', ''));
        $perPage = (int) request('per_page', 30);
        $perPage = max(10, min($perPage, 100));

        $users = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name',  'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();

        $assignedUsers = $role->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        return view('admin.roles.assign-users', compact('role', 'users', 'assignedUsers', 'search'));
    }

    public function syncUsers(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'users'   => ['nullable', 'array'],
            'users.*' => ['integer', 'exists:users,id'],
        ]);

        $previousUserIds = $role->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
        $newUserIds      = array_map('intval', $data['users'] ?? []);

        // ✅ Une seule fois, réutilisé partout
        $affectedUserIds = array_values(array_unique(
            array_merge($previousUserIds, $newUserIds)
        ));

        try {
            DB::transaction(function () use ($role, $newUserIds, $affectedUserIds): void {
                $role->users()->sync($newUserIds);
                $this->syncPrimaryRoleColumnForUsers($affectedUserIds);
            });

            // ✅ Invalide le cache des users ajoutés/retirés
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Utilisateurs synchronisés pour le rôle', [
                'role_id'        => $role->id,
                'added'          => count(array_diff($newUserIds, $previousUserIds)),
                'removed'        => count(array_diff($previousUserIds, $newUserIds)),
                'total'          => count($newUserIds),
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.roles.assign-users', $role)
                ->with('success', 'Utilisateurs du rôle mis à jour avec succès.');

        } catch (Throwable $e) {
            Log::error('Erreur synchro utilisateurs rôle', [
                'role_id'  => $role->id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la synchronisation.');
        }
    }

    // ============================================================
    // PERMISSIONS
    // ============================================================

    public function permissions(Role $role): View
    {
        $permissions     = $this->getPermissionsGrouped();
        $rolePermissions = $this->rolePermissionIds($role);

        return view('admin.roles.permissions', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * ✅ Invalide les caches des utilisateurs liés au rôle modifié.
     */
    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        // ✅ Capture les users du rôle AVANT de modifier
        $affectedUserIds = $role->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        try {
            $count = count($data['permissions'] ?? []);

            DB::transaction(function () use ($role, $data): void {
                $this->syncRolePermissions($role, $data['permissions'] ?? []);
            });

            $this->flushPermissionsCache();

            // ✅ Invalide le cache de TOUS les users du rôle
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Permissions synchronisées pour le rôle', [
                'role_id'        => $role->id,
                'count'          => $count,
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.roles.index')
                ->with('success', "{$count} permission(s) attribuée(s) au rôle « {$role->name} ». "
                    . count($affectedUserIds) . " utilisateur(s) mis à jour.");

        } catch (Throwable $e) {
            Log::error('Erreur synchro permissions', [
                'role_id'  => $role->id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de la synchronisation.');
        }
    }

    /**
     * Attribue toutes les permissions au rôle admin.
     */
    public function syncAllPermissionsToAdmin(): RedirectResponse
    {
        $adminRole = Role::firstOrCreate(
            ['name' => self::AUTO_ADMIN_ROLE],
            ['label' => 'Administrateur', 'description' => 'Rôle administrateur principal'],
        );

        $allPermissionIds = Permission::pluck('id')->map(fn ($id) => (int) $id)->all();
        $count            = count($allPermissionIds);

        if ($count === 0) {
            return back()->with('error', 'Aucune permission à attribuer.');
        }

        // ✅ Capture les users AVANT
        $affectedUserIds = $adminRole->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        try {
            DB::transaction(function () use ($adminRole, $allPermissionIds): void {
                $this->syncRolePermissions($adminRole, $allPermissionIds);
            });

            $this->flushPermissionsCache();

            // ✅ Invalide le cache des users du rôle admin
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Toutes les permissions attribuées au rôle admin', [
                'role_id'        => $adminRole->id,
                'count'          => $count,
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return back()->with('success', "{$count} permission(s) attribuée(s) au rôle « admin ». "
                . count($affectedUserIds) . " utilisateur(s) mis à jour.");

        } catch (Throwable $e) {
            Log::error('Erreur attribution permissions admin', [
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->with('error', 'Une erreur est survenue lors de l\'attribution.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES
    // ============================================================

    /**
     * ✅ Invalide le cache des permissions pour une liste d'utilisateurs.
     *
     * Point d'entrée public (dans le controller) qui délègue à forgetUserCache()
     * pour garantir qu'on utilise TOUJOURS le bon préfixe + nettoie les legacy.
     */
    private function invalidateUserPermissionCaches(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        $uniqueIds = array_unique($userIds);

        foreach ($uniqueIds as $userId) {
            $this->forgetUserCache((int) $userId);
        }

        Log::debug('Caches utilisateurs invalidés', [
            'count' => count($uniqueIds),
        ]);
    }

    /**
     * ✅ Point unique de suppression des clés de cache d'un utilisateur.
     *
     * Identique à SyncPermissions::forgetUserCache().
     */
    private function forgetUserCache(int $userId): void
    {
        // Clé actuelle (celle utilisée par User::getAllPermissionNames)
        Cache::forget(self::CACHE_USER_PERMS_PREFIX . $userId);

        // 🧹 Legacy — à retirer dans une future version
        Cache::forget("user_permissions_{$userId}");
        Cache::forget("user_roles_{$userId}");
    }

    private function systemRoles(): array
    {
        return array_merge(self::PROTECTED_ROLES, [self::AUTO_ADMIN_ROLE]);
    }

    private function rolePermissionIds(Role $role): array
    {
        return $role->permissions()
            ->pluck('permissions.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function validateRole(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:' . self::NAME_MAX,
                'regex:/^[a-z0-9_\-]+$/',
                Rule::unique('roles', 'name')->ignore($ignoreId),
            ],
            'label'         => ['nullable', 'string', 'max:' . self::NAME_MAX],
            'description'   => ['nullable', 'string', 'max:' . self::DESCRIPTION_MAX],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], [
            'name.required'   => 'Le nom du rôle est obligatoire.',
            'name.regex'      => 'Le nom ne peut contenir que des minuscules, chiffres, tirets et underscores.',
            'name.unique'     => 'Un rôle avec ce nom existe déjà.',
            'name.max'        => 'Le nom ne doit pas dépasser :max caractères.',
            'label.max'       => 'Le libellé ne doit pas dépasser :max caractères.',
            'description.max' => 'La description ne doit pas dépasser :max caractères.',
        ]);
    }

    private function getPermissionsGrouped(): array
    {
        return Cache::remember(self::CACHE_PERMISSIONS, self::CACHE_TTL, function (): array {
            $permissions = Permission::query()
                ->orderBy('resource')
                ->orderBy('action')
                ->get(['id', 'name', 'label', 'resource', 'action']);

            $grouped = [
                self::SCOPE_ADMIN  => [],
                self::SCOPE_MEMBER => [],
            ];

            foreach ($permissions as $perm) {
                $scope    = $this->resolveScope((string) $perm->name);
                $resource = $perm->resource ?: 'autres';

                $grouped[$scope][$resource][] = [
                    'id'     => (int) $perm->id,
                    'name'   => $perm->name,
                    'label'  => $perm->label ?? $perm->name,
                    'action' => $perm->action,
                ];
            }

            return $grouped;
        });
    }

    private function resolveScope(string $permissionName): string
    {
        foreach (self::SCOPE_MAP as $prefix => $scope) {
            if (str_starts_with($permissionName, $prefix)) {
                return $scope;
            }
        }

        return self::SCOPE_ADMIN;
    }

    private function flushPermissionsCache(): void
    {
        Cache::forget(self::CACHE_PERMISSIONS);
        Cache::forget(self::CACHE_RESOURCES);
    }

    private function syncRolePermissions(Role $role, array $permissionIds): void
    {
        $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));
        $role->permissions()->sync($permissionIds);
    }

    private function syncPrimaryRoleColumnForUsers(array $userIds): void
    {
        $userIds = array_values(array_unique(array_filter(
            $userIds,
            fn ($id) => (int) $id > 0
        )));

        if (empty($userIds)) {
            return;
        }

        $users = User::query()
            ->with(['roles' => fn ($q) => $q->orderBy('id')])
            ->whereIn('id', $userIds)
            ->get(['id', 'role']);

        if ($users->isEmpty()) {
            return;
        }

        $updates = [];

        foreach ($users as $user) {
            $primaryRole = $user->roles->first();
            $newRoleName = $primaryRole?->name ?? self::DEFAULT_FALLBACK_ROLE;

            if ($user->role !== $newRoleName) {
                $updates[$user->id] = $newRoleName;
            }
        }

        if (! empty($updates)) {
            DB::transaction(function () use ($updates): void {
                collect($updates)
                    ->groupBy(fn ($roleName) => $roleName)
                    ->each(function ($ids, $roleName): void {
                        User::whereIn('id', $ids->keys()->all())
                            ->update(['role' => $roleName]);
                    });
            });
        }
    }

    private function isProtectedRole(Role $role): bool
    {
        return in_array($role->name, self::PROTECTED_ROLES, true);
    }
}