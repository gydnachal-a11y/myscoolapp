<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class PermissionController extends Controller
{
    // ============================================================
    // CONSTANTES
    // ============================================================

    private const PER_PAGE        = 25;
    private const PER_PAGE_MAX    = 100;
    private const PER_PAGE_MIN    = 5;
    private const USERS_PER_PAGE  = 50;
    private const NAME_MAX        = 255;
    private const LABEL_MAX       = 255;
    private const DESCRIPTION_MAX = 500;
    private const RESOURCE_MAX    = 100;
    private const ACTION_MAX      = 100;
    private const SEARCH_MAX      = 100;

    /** ✅ Cache partagé — public pour cohérence avec RoleController. */
    public const CACHE_PERMISSIONS   = 'permissions_grouped_v1';
    public const CACHE_RESOURCES     = 'permissions_resources_v1';
    private const CACHE_BY_RESOURCE  = 'permissions_by_resource_v1';
    private const CACHE_ALL_NAMES    = 'permissions_all_names_v1';
    private const CACHE_TTL          = 600;

    /** ✅ Préfixe du cache par utilisateur (aligné avec User::getAllPermissionNames). */
    private const CACHE_USER_PERMS_PREFIX = 'user_all_permissions_';

    /** Préfixes des scopes. */
    private const SCOPE_ADMIN  = 'admin';
    private const SCOPE_MEMBER = 'member';

    /** Map préfixe → scope (identique à RoleController). */
    private const SCOPE_MAP = [
        'member.' => self::SCOPE_MEMBER,
        'admin.'  => self::SCOPE_ADMIN,
    ];

    /** Options de tri autorisées. */
    private const SORT_OPTIONS = [
        'name_asc',
        'name_desc',
        'resource_asc',
        'resource_desc',
        'roles_desc',
        'roles_asc',
    ];

    /** Tailles de pagination autorisées. */
    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    // ============================================================
    // INDEX
    // ============================================================

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search'   => ['nullable', 'string', 'max:' . self::SEARCH_MAX],
            'resource' => ['nullable', 'string', 'max:' . self::RESOURCE_MAX],
            'scope'    => ['nullable', 'in:all,admin,member'],
            'sort'     => ['nullable', 'in:' . implode(',', self::SORT_OPTIONS)],
            'per_page' => ['nullable', 'integer', 'min:' . self::PER_PAGE_MIN, 'max:' . self::PER_PAGE_MAX],
        ]);

        $search   = trim((string) ($validated['search'] ?? ''));
        $resource = $validated['resource'] ?? null;
        $scope    = $validated['scope'] ?? 'all';
        $sort     = $validated['sort'] ?? 'name_asc';

        $perPage = (int) ($validated['per_page'] ?? self::PER_PAGE);
        if (! in_array($perPage, self::PER_PAGE_OPTIONS, true)) {
            $perPage = self::PER_PAGE;
        }

        $query = Permission::query()
            ->withCount('roles')
            ->when($search !== '', function ($q) use ($search): void {
                $q->where(function ($sub) use ($search): void {
                    $sub->where('name',        'LIKE', "%{$search}%")
                        ->orWhere('label',       'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%");
                });
            })
            ->when($resource, fn ($q) => $q->where('resource', $resource))
            ->when($scope === self::SCOPE_ADMIN,  fn ($q) => $q->where('name', 'NOT LIKE', 'member.%'))
            ->when($scope === self::SCOPE_MEMBER, fn ($q) => $q->where('name', 'LIKE', 'member.%'));

        match ($sort) {
            'name_desc'     => $query->orderByDesc('name'),
            'resource_asc'  => $query->orderBy('resource')->orderBy('action'),
            'resource_desc' => $query->orderByDesc('resource')->orderBy('action'),
            'roles_desc'    => $query->orderByDesc('roles_count')->orderBy('name'),
            'roles_asc'     => $query->orderBy('roles_count')->orderBy('name'),
            default         => $query->orderBy('name'),
        };

        $permissions = $query->paginate($perPage)->withQueryString();
        $resources   = $this->getDistinctResources();

        return view('admin.permissions.index', compact(
            'permissions',
            'resources',
            'search',
            'resource',
            'scope',
            'sort',
        ));
    }

    // ============================================================
    // CREATE / STORE
    // ============================================================

    /**
     * ✅ CORRECTION : passe les 3 variables attendues par la vue.
     */
    public function create(): View
    {
        return view('admin.permissions.create', [
            'resources'             => $this->getDistinctResources(),
            'permissionsByResource' => $this->getPermissionsByResource(),
            'allPermissions'        => $this->getAllPermissionNames(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePermission($request);

        try {
            $permission = DB::transaction(fn () => Permission::create($validated));

            $this->flushPermissionsCache();

            Log::info('Permission créée', [
                'permission_id' => $permission->id,
                'name'          => $validated['name'],
                'actor_id'      => auth()->id(),
            ]);

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', "Permission « {$permission->name} » créée avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur création permission', [
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création.');
        }
    }

    // ============================================================
    // EDIT / UPDATE
    // ============================================================

    /**
     * ✅ CORRECTION : passe $permission + les 3 variables attendues par la vue.
     */
    public function edit(Permission $permission): View
    {
        return view('admin.permissions.edit', [
            'permission'            => $permission,
            'resources'             => $this->getDistinctResources(),
            'permissionsByResource' => $this->getPermissionsByResource(),
            'allPermissions'        => $this->getAllPermissionNames(),
        ]);
    }

    public function update(Request $request, Permission $permission): RedirectResponse
    {
        $validated = $this->validatePermission($request, (int) $permission->id);

        $affectedUserIds = $this->getUserIdsWithPermission((int) $permission->id);

        try {
            DB::transaction(fn () => $permission->update($validated));

            $this->flushPermissionsCache();
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Permission mise à jour', [
                'permission_id'  => $permission->id,
                'name'           => $validated['name'],
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', "Permission « {$permission->name} » mise à jour avec succès.");

        } catch (Throwable $e) {
            Log::error('Erreur mise à jour permission', [
                'permission_id' => $permission->id,
                'error'         => $e->getMessage(),
                'actor_id'      => auth()->id(),
            ]);

            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour.');
        }
    }

    // ============================================================
    // DESTROY
    // ============================================================

    public function destroy(Permission $permission): RedirectResponse
    {
        $permissionId   = (int) $permission->id;
        $permissionName = (string) $permission->name;

        $affectedUserIds = $this->getUserIdsWithPermission($permissionId);

        try {
            DB::transaction(function () use ($permission): void {
                $rolesCount = DB::table('permission_role')
                    ->where('permission_id', $permission->id)
                    ->lockForUpdate()
                    ->count();

                if ($rolesCount > 0) {
                    $plural = $rolesCount > 1 ? 's' : '';
                    throw new \RuntimeException(
                        "Impossible de supprimer cette permission : elle est utilisée par "
                        . "{$rolesCount} rôle{$plural}. Détachez-la d'abord."
                    );
                }

                $permission->delete();
            });

            $this->flushPermissionsCache();
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Permission supprimée', [
                'permission_id'  => $permissionId,
                'name'           => $permissionName,
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', "Permission « {$permissionName} » supprimée avec succès.");

        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());

        } catch (Throwable $e) {
            Log::error('Erreur suppression permission', [
                'permission_id' => $permissionId,
                'error'         => $e->getMessage(),
                'actor_id'      => auth()->id(),
            ]);

            return back()->with('error', 'Impossible de supprimer cette permission.');
        }
    }

    // ============================================================
    // MANAGE
    // ============================================================

    public function manage(Request $request): View
    {
        $users = User::query()
            ->select(['id', 'name', 'email', 'role'])
            ->orderBy('name')
            ->paginate(self::USERS_PER_PAGE, ['*'], 'users_page')
            ->withQueryString();

        $roles = Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'label']);

        $grouped = $this->getPermissionsGroupedByScope();

        return view('admin.permissions.manage', compact('roles', 'users', 'grouped'));
    }

    // ============================================================
    // ENDPOINTS JSON
    // ============================================================

    public function getTargetPermissions(string $type, int $id): JsonResponse
    {
        try {
            $permissions = match ($type) {
                'role' => Role::query()
                    ->with('permissions:id,name')
                    ->findOrFail($id)
                    ->permissions
                    ->pluck('name'),

                'user' => User::query()
                    ->with('permissions:id,name')
                    ->findOrFail($id)
                    ->permissions
                    ->pluck('name'),

                default => throw new \InvalidArgumentException('Type de cible inconnu.'),
            };

            return response()->json([
                'success'     => true,
                'permissions' => $permissions->values(),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Cible introuvable.',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Erreur récupération permissions cible', [
                'type'     => $type,
                'id'       => $id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Erreur lors de la récupération.',
            ], 500);
        }
    }

    public function syncTargetPermissions(Request $request, string $type, int $id): JsonResponse
    {
        $validated = $request->validate([
            'permissions'   => ['present', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $permissionNames = $validated['permissions'] ?? [];

        try {
            $permissionIds = Permission::query()
                ->whereIn('name', $permissionNames)
                ->pluck('id')
                ->all();

            $affectedUserIds = $this->resolveAffectedUsers($type, $id);

            DB::transaction(function () use ($type, $id, $permissionIds): void {
                match ($type) {
                    'role' => Role::findOrFail($id)
                        ->permissions()
                        ->sync($permissionIds),

                    'user' => User::findOrFail($id)
                        ->permissions()
                        ->sync($permissionIds),

                    default => throw new \InvalidArgumentException('Type de cible inconnu.'),
                };
            });

            $this->flushPermissionsCache();
            $this->invalidateUserPermissionCaches($affectedUserIds);

            Log::info('Permissions synchronisées', [
                'type'           => $type,
                'target_id'      => $id,
                'count'          => count($permissionIds),
                'users_affected' => count($affectedUserIds),
                'actor_id'       => auth()->id(),
            ]);

            return response()->json([
                'success'        => true,
                'users_affected' => count($affectedUserIds),
            ]);

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 400);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'error'   => 'Cible introuvable.',
            ], 404);

        } catch (Throwable $e) {
            Log::error('Erreur synchronisation permissions', [
                'type'     => $type,
                'id'       => $id,
                'error'    => $e->getMessage(),
                'actor_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => 'Erreur lors de la synchronisation.',
            ], 500);
        }
    }

    // ============================================================
    // SYNCHRONISATION AUTOMATIQUE
    // ============================================================

    public function syncAll(): RedirectResponse
    {
        return $this->runSyncCommand(
            ['--force' => true],
            'Synchronisation des permissions terminée.',
        );
    }

    public function syncAllAndAssignAdmin(): RedirectResponse
    {
        return $this->runSyncCommand(
            [
                '--force'        => true,
                '--assign-admin' => true,
            ],
            'Permissions synchronisées et attribuées au rôle admin.',
        );
    }

    private function runSyncCommand(array $args, string $successMessage): RedirectResponse
    {
        try {
            Artisan::call('permissions:sync', $args);

            $this->flushPermissionsCache();

            Log::info('Permissions synchronisées via artisan', [
                'args'     => $args,
                'actor_id' => auth()->id(),
            ]);

            return redirect()
                ->route('admin.permissions.index')
                ->with('success', $successMessage);

        } catch (Throwable $e) {
            Log::error('Erreur synchronisation artisan', [
                'error'    => $e->getMessage(),
                'args'     => $args,
                'actor_id' => auth()->id(),
            ]);

            return back()->with('error', 'Erreur lors de la synchronisation automatique.');
        }
    }

    // ============================================================
    // MÉTHODES PRIVÉES — CACHE
    // ============================================================

    /**
     * Liste distincte des ressources (cachée 10 min).
     *
     * @return array<int, string>
     */
    private function getDistinctResources(): array
    {
        return Cache::remember(self::CACHE_RESOURCES, self::CACHE_TTL, function (): array {
            return Permission::query()
                ->select('resource')
                ->whereNotNull('resource')
                ->where('resource', '!=', '')
                ->distinct()
                ->orderBy('resource')
                ->pluck('resource')
                ->all();
        });
    }

    /**
     * ✅ NOUVEAU : permissions groupées par ressource.
     *
     * Structure : [
     *   'paiements' => ['create', 'destroy', 'edit', 'index', ...],
     *   'eleves'    => ['create', 'index', ...],
     * ]
     *
     * Utilisé par create() et edit() pour :
     *   - Afficher les actions disponibles
     *   - Détecter les doublons
     *   - Alimenter le bulk-create
     *
     * @return array<string, array<int, string>>
     */
    private function getPermissionsByResource(): array
    {
        return Cache::remember(self::CACHE_BY_RESOURCE, self::CACHE_TTL, function (): array {
            $grouped = [];

            Permission::query()
                ->select(['name', 'resource', 'action'])
                ->orderBy('resource')
                ->orderBy('action')
                ->get()
                ->each(function (Permission $perm) use (&$grouped): void {
                    // Priorité : colonne resource > déduction depuis name
                    $resource = $perm->resource
                        ?: (str_contains((string) $perm->name, '.')
                            ? explode('.', (string) $perm->name, 2)[0]
                            : 'autres');

                    // Priorité : colonne action > déduction depuis name
                    $action = $perm->action
                        ?: (str_contains((string) $perm->name, '.')
                            ? explode('.', (string) $perm->name, 2)[1]
                            : '');

                    if ($action !== '') {
                        $grouped[$resource][] = $action;
                    }
                });

            // Tri + dédoublonnage par ressource
            foreach ($grouped as $resource => $actions) {
                $grouped[$resource] = array_values(array_unique($actions));
                sort($grouped[$resource]);
            }

            ksort($grouped);

            return $grouped;
        });
    }

    /**
     * ✅ NOUVEAU : liste plate de tous les noms de permissions.
     *
     * Utilisée par create() et edit() pour détecter les doublons
     * côté client (Alpine).
     *
     * @return array<int, string>
     */
    private function getAllPermissionNames(): array
    {
        return Cache::remember(self::CACHE_ALL_NAMES, self::CACHE_TTL, function (): array {
            return Permission::query()
                ->orderBy('name')
                ->pluck('name')
                ->all();
        });
    }

    /**
     * Regroupe les permissions par scope puis par ressource.
     */
    private function getPermissionsGroupedByScope(): array
    {
        return Cache::remember(self::CACHE_PERMISSIONS, self::CACHE_TTL, function (): array {
            $allPermissions = Permission::query()
                ->orderBy('resource')
                ->orderBy('action')
                ->get(['id', 'name', 'label', 'resource', 'action']);

            $grouped = [
                self::SCOPE_ADMIN  => [],
                self::SCOPE_MEMBER => [],
            ];

            foreach ($allPermissions as $permission) {
                $scope    = $this->resolveScope((string) $permission->name);
                $resource = $permission->resource ?: 'autres';

                $grouped[$scope][$resource][] = [
                    'id'     => (int) $permission->id,
                    'name'   => (string) $permission->name,
                    'label'  => (string) ($permission->label ?? $permission->name),
                    'action' => (string) $permission->action,
                ];
            }

            return $grouped;
        });
    }

    /**
     * Résout le scope d'une permission en fonction de son préfixe.
     */
    private function resolveScope(string $permissionName): string
    {
        foreach (self::SCOPE_MAP as $prefix => $scope) {
            if (str_starts_with($permissionName, $prefix)) {
                return $scope;
            }
        }

        return self::SCOPE_ADMIN;
    }

    /**
     * ✅ CORRECTION : vide TOUTES les clés de cache liées aux permissions.
     *
     * Ajout de CACHE_BY_RESOURCE + CACHE_ALL_NAMES.
     */
    private function flushPermissionsCache(): void
    {
        Cache::forget(self::CACHE_PERMISSIONS);
        Cache::forget(self::CACHE_RESOURCES);
        Cache::forget(self::CACHE_BY_RESOURCE);
        Cache::forget(self::CACHE_ALL_NAMES);
    }

    // ============================================================
    // MÉTHODES PRIVÉES — UTILISATEURS IMPACTÉS
    // ============================================================

    private function resolveAffectedUsers(string $type, int $id): array
    {
        return match ($type) {
            'role' => Role::findOrFail($id)
                ->users()
                ->pluck('users.id')
                ->map(fn ($uid) => (int) $uid)
                ->all(),

            'user' => [(int) $id],

            default => [],
        };
    }

    private function getUserIdsWithPermission(int $permissionId): array
    {
        $directIds = DB::table('permission_user')
            ->where('permission_id', $permissionId)
            ->pluck('user_id')
            ->all();

        $viaRoleUserIds = DB::table('permission_role')
            ->where('permission_role.permission_id', $permissionId)
            ->join('role_user', 'role_user.role_id', '=', 'permission_role.role_id')
            ->pluck('role_user.user_id')
            ->all();

        $roleNames = DB::table('permission_role')
            ->where('permission_role.permission_id', $permissionId)
            ->join('roles', 'roles.id', '=', 'permission_role.role_id')
            ->pluck('roles.name')
            ->all();

        $viaPrimaryRoleIds = empty($roleNames)
            ? []
            : DB::table('users')
                ->whereIn('role', $roleNames)
                ->pluck('id')
                ->all();

        return array_values(array_unique(array_map('intval', array_merge(
            $directIds,
            $viaRoleUserIds,
            $viaPrimaryRoleIds,
        ))));
    }

    // ============================================================
    // MÉTHODES PRIVÉES — INVALIDATION CACHE UTILISATEUR
    // ============================================================

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

    private function forgetUserCache(int $userId): void
    {
        Cache::forget(self::CACHE_USER_PERMS_PREFIX . $userId);
        Cache::forget("user_permissions_{$userId}");
        Cache::forget("user_roles_{$userId}");
    }

    // ============================================================
    // VALIDATION
    // ============================================================

    private function validatePermission(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:' . self::NAME_MAX,
                'regex:/^[a-z0-9_\.\-]+$/',
                Rule::unique('permissions', 'name')->ignore($ignoreId),
            ],
            'label'       => ['nullable', 'string', 'max:' . self::LABEL_MAX],
            'description' => ['nullable', 'string', 'max:' . self::DESCRIPTION_MAX],
            'resource'    => ['nullable', 'string', 'max:' . self::RESOURCE_MAX],
            'action'      => ['nullable', 'string', 'max:' . self::ACTION_MAX],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'name.unique'   => 'Cette permission existe déjà.',
            'name.regex'    => 'Le nom ne peut contenir que des minuscules, chiffres, points, tirets et underscores.',
            'name.max'      => 'Le nom ne doit pas dépasser :max caractères.',
            'label.max'       => 'Le libellé ne doit pas dépasser :max caractères.',
            'description.max' => 'La description ne doit pas dépasser :max caractères.',
            'resource.max'    => 'La ressource ne doit pas dépasser :max caractères.',
            'action.max'      => "L'action ne doit pas dépasser :max caractères.",
        ]);
    }
}