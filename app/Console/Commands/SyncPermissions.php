<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Throwable;

class SyncPermissions extends Command
{
    // ============================================================
    // SIGNATURE
    // ============================================================

    protected $signature = 'permissions:sync
                            {--force : Met à jour les permissions existantes avec les nouvelles valeurs resource/action}
                            {--assign-admin : Attribue toutes les permissions au rôle admin après synchronisation}
                            {--only= : Ne synchronise que les ressources spécifiées (ex: statistiques,eleves)}
                            {--dry-run : Affiche ce qui serait fait sans rien modifier en base}';

    protected $description = 'Synchronise les permissions à partir des routes nommées (admin.*)';

    // ============================================================
    // CONSTANTES
    // ============================================================

    private const ADMIN_PREFIX = 'admin.';

    /** ✅ Cache global des permissions groupées (aligné avec les 2 controllers). */
    private const CACHE_PERMISSIONS = 'permissions_grouped_v1';
    private const CACHE_RESOURCES   = 'permissions_resources_v1';

    /** ✅ CORRECTION #1 : préfixe du cache par utilisateur, aligné sur User::getAllPermissionNames(). */
    private const CACHE_USER_PERMS_PREFIX = 'user_all_permissions_';

    /** Rôle recevant automatiquement toutes les permissions. */
    private const AUTO_ADMIN_ROLE = 'admin';

    private const ROUTES_EXCLUES = [
        'admin.redirect',
        'admin.home',
    ];

    private const ACTION_LABELS = [
        'index'                 => 'Voir la liste',
        'create'                => 'Créer',
        'store'                 => 'Enregistrer',
        'show'                  => 'Voir le détail',
        'edit'                  => 'Modifier',
        'update'                => 'Mettre à jour',
        'destroy'               => 'Supprimer',
        'toggle-active'         => 'Activer/Désactiver',
        'toggle-cloture'        => 'Clôturer/Rouvrir',
        'toggle-paiement'       => 'Ouvrir/Fermer paiements',
        'export'                => 'Exporter',
        'export.pdf'            => 'Exporter PDF',
        'export.csv'            => 'Exporter CSV',
        'export.xml'            => 'Exporter XML',
        'export.word'           => 'Exporter Word',
        'imprimer'              => 'Imprimer',
        'sync-users'            => 'Synchroniser utilisateurs',
        'sync-permissions'      => 'Synchroniser permissions',
        'assign-users'          => 'Assigner utilisateurs',
        'planifier-toutes'      => 'Tout planifier',
        'generer'               => 'Générer',
        'vider'                 => 'Vider',
        'corbeille'             => 'Corbeille',
        'restaurer'             => 'Restaurer',
        'force-delete'          => 'Supprimer définitivement',
        'manage'                => 'Gérer',
        'bulk-create'           => 'Créer en masse',
        'sync-all'              => 'Tout synchroniser',
        'sync-all-assign-admin' => 'Tout synchroniser + assigner admin',
        'fermer'                => 'Fermer',
        'valider'               => 'Valider',
        'refuser'               => 'Refuser',
        'toggle'                => 'Activer/Désactiver',
        'activer'               => 'Activer',
    ];

    // ============================================================
    // HANDLE
    // ============================================================

    public function handle(): int
    {
        $dryRun      = (bool) $this->option('dry-run');
        $assignAdmin = (bool) $this->option('assign-admin');

        if ($dryRun) {
            $this->warn('🧪 Mode DRY-RUN activé — aucune modification en base.');
            $this->newLine();
        }

        $this->info('🔄 Synchronisation des permissions en cours...');
        $this->newLine();

        try {
            /* =========================================================
             | 1. Collecte des permissions depuis les routes
             | ========================================================= */
            $permissionsData = $this->collecterPermissions();

            if (empty($permissionsData)) {
                $this->warn('⚠️  Aucune permission candidate trouvée.');
                return self::SUCCESS;
            }

            $this->info('📋 ' . count($permissionsData) . ' permission(s) candidate(s) trouvée(s).');
            $this->newLine();

            /* =========================================================
             | 2. Synchronisation (dry-run supporté)
             | ========================================================= */
            [$countCreated, $countUpdated] = $this->synchroniser($permissionsData, $dryRun);

            if ($dryRun) {
                $this->newLine();
                $this->info('🧪 DRY-RUN — Synthèse prévisionnelle :');
                $this->line("   ✅ {$countCreated} permission(s) seraient créée(s)");
                $this->line("   🔄 {$countUpdated} permission(s) seraient mise(s) à jour");
                $this->newLine();
                $this->warn('⚠️  Aucune modification n\'a été appliquée.');
                return self::SUCCESS;
            }

            /* =========================================================
             | 3. Invalidation des caches globaux
             | ========================================================= */
            $this->flushPermissionsCache();

            /* =========================================================
             | 4. Attribution admin si demandé
             |    (la méthode invalide déjà ses propres users)
             | ========================================================= */
            if ($assignAdmin) {
                $this->newLine();
                $this->assignAllPermissionsToAdmin();
            }

            /* =========================================================
             | 5. ✅ Invalidation ciblée des caches users
             |
             | CORRECTION #2 : on n'invalide QUE si quelque chose a bougé,
             | sinon on gaspille du CPU pour rien à chaque exécution.
             | ========================================================= */
            $cachesWereInvalidated = false;

            if ($countCreated > 0 || $countUpdated > 0 || $assignAdmin) {
                $this->invalidateAllUserCaches();
                $cachesWereInvalidated = true;
            } else {
                $this->line('  ℹ️  Aucun changement détecté — caches utilisateurs conservés.');
            }

            /* =========================================================
             | 6. Résumé
             | ========================================================= */
            $this->newLine();
            $this->info('📊 Synthèse :');
            $this->line("   ✅ {$countCreated} permission(s) créée(s)");
            $this->line("   🔄 {$countUpdated} permission(s) mise(s) à jour");
            $this->newLine();
            $this->info('✅ Synchronisation terminée.');

            Log::info('SyncPermissions exécutée', [
                'created'        => $countCreated,
                'updated'        => $countUpdated,
                'assign_admin'   => $assignAdmin,
                'only'           => $this->option('only'),
                'caches_flushed' => $cachesWereInvalidated,
                'actor_id'       => $this->resolveActorId(),
            ]);

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->newLine();
            $this->error('❌ Erreur lors de la synchronisation :');
            $this->error("   {$e->getMessage()}");

            Log::error('SyncPermissions erreur', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }

    // ============================================================
    // ÉTAPE 1 — COLLECTE
    // ============================================================

    /**
     * Parcourt les routes et extrait les permissions candidates.
     *
     * @return array<string, array{name: string, resource: string, action: string, label: string}>
     */
    private function collecterPermissions(): array
    {
        $routes      = Route::getRoutes();
        $permissions = [];
        $onlyFilter  = $this->resolveOnlyFilter();

        foreach ($routes as $route) {
            $name = $route->getName();

            /* ----- Filtres rapides ----- */
            if (! $name || ! str_starts_with($name, self::ADMIN_PREFIX)) {
                continue;
            }

            if (in_array($name, self::ROUTES_EXCLUES, true)) {
                continue;
            }

            $permName = substr($name, strlen(self::ADMIN_PREFIX));

            if (! str_contains($permName, '.')) {
                $this->warn("⚠️  Route sans point ignorée : {$name}");
                continue;
            }

            [$resource, $action] = $this->extractResourceAndAction($permName);

            if ($resource === '' || $action === '') {
                $this->warn("⚠️  Impossible d'extraire resource/action pour : {$name}");
                continue;
            }

            if ($onlyFilter !== null && ! in_array($resource, $onlyFilter, true)) {
                continue;
            }

            if (! isset($permissions[$permName])) {
                $permissions[$permName] = [
                    'name'     => $permName,
                    'resource' => $resource,
                    'action'   => $action,
                    'label'    => $this->generateLabel($resource, $action),
                ];
            }
        }

        return $permissions;
    }

    // ============================================================
    // ÉTAPE 2 — SYNCHRONISATION
    // ============================================================

    /**
     * Synchronise les permissions en base.
     *
     * ⚡ Optimisation : 1 requête pour charger toutes les permissions,
     *    puis batch insert/update.
     *
     * @param  array<string, array>  $permissionsData
     * @return array{0: int, 1: int} [created, updated]
     */
    private function synchroniser(array $permissionsData, bool $dryRun = false): array
    {
        $existingPermissions = Permission::query()
            ->whereIn('name', array_keys($permissionsData))
            ->get()
            ->keyBy('name');

        $now          = now();
        $toInsert     = [];
        $toUpdate     = [];
        $countCreated = 0;
        $countUpdated = 0;
        $force        = (bool) $this->option('force');

        foreach ($permissionsData as $name => $data) {
            $existing = $existingPermissions->get($name);

            /* ----- Nouvelle permission ----- */
            if (! $existing) {
                $toInsert[] = [
                    'name'       => $data['name'],
                    'resource'   => $data['resource'],
                    'action'     => $data['action'],
                    'label'      => $data['label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $countCreated++;
                $this->line("  ✅ <fg=green>Créé</>       {$data['name']}");
                continue;
            }

            /* ----- Permission existante — mode force ----- */
            if ($force) {
                $toUpdate[] = [
                    'id'         => $existing->id,
                    'resource'   => $data['resource'],
                    'action'     => $data['action'],
                    'label'      => $data['label'],
                    'updated_at' => $now,
                ];
                $countUpdated++;
                $this->line("  🔄 <fg=yellow>Mis à jour</> {$data['name']}");
                continue;
            }

            /* ----- Mode doux : complète uniquement les champs vides ----- */
            $fields      = [];
            $needsUpdate = false;

            if (empty($existing->resource) && ! empty($data['resource'])) {
                $fields['resource'] = $data['resource'];
                $needsUpdate = true;
            }
            if (empty($existing->action) && ! empty($data['action'])) {
                $fields['action'] = $data['action'];
                $needsUpdate = true;
            }
            if (empty($existing->label) && ! empty($data['label'])) {
                $fields['label'] = $data['label'];
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $fields['updated_at'] = $now;
                $toUpdate[]           = array_merge(['id' => $existing->id], $fields);
                $countUpdated++;
                $this->line("  🔄 <fg=blue>Complété</>   {$data['name']}");
            }
        }

        /* ----- DRY-RUN : on s'arrête là ----- */
        if ($dryRun) {
            return [$countCreated, $countUpdated];
        }

        /* ----- Écriture en base ----- */
        if (empty($toInsert) && empty($toUpdate)) {
            return [0, 0];
        }

        DB::transaction(function () use ($toInsert, $toUpdate): void {
            if (! empty($toInsert)) {
                // ✅ upsert pour éviter les duplicats en race condition
                Permission::upsert(
                    $toInsert,
                    ['name'],
                    ['resource', 'action', 'label', 'updated_at']
                );
            }

            if (! empty($toUpdate)) {
                // ✅ Batch update via CASE WHEN pour éviter le N+1
                $this->batchUpdatePermissions($toUpdate);
            }
        });

        return [$countCreated, $countUpdated];
    }

    // ============================================================
    // ÉTAPE 3 — ATTRIBUTION AU RÔLE ADMIN
    // ============================================================

    private function assignAllPermissionsToAdmin(): void
    {
        $adminRole = Role::firstOrCreate(
            ['name' => self::AUTO_ADMIN_ROLE],
            ['label' => 'Administrateur', 'description' => 'Rôle administrateur principal'],
        );

        $allPermissionIds = Permission::pluck('id')->all();
        $total            = count($allPermissionIds);

        if ($total === 0) {
            $this->warn('⚠️  Aucune permission à attribuer.');
            return;
        }

        // ✅ CORRECTION #3 : on capture les users AVANT la modification
        $affectedUserIds = $adminRole->users()->pluck('users.id')->map(fn ($id) => (int) $id)->all();

        try {
            DB::transaction(function () use ($adminRole, $allPermissionIds): void {
                $adminRole->permissions()->sync($allPermissionIds);
            });

            $this->flushPermissionsCache();

            // ✅ CORRECTION #3 (suite) : on invalide le cache des users du rôle admin
            $this->invalidateUserCachesForIds($affectedUserIds);

            $this->info("🔐 {$total} permission(s) attribuée(s) au rôle « admin ».");
            $this->line('   🧹 ' . count($affectedUserIds) . ' cache(s) utilisateur(s) invalidé(s).');

            Log::info('Permissions attribuées automatiquement au rôle admin', [
                'role_id'        => $adminRole->id,
                'count'          => $total,
                'users_affected' => count($affectedUserIds),
                'actor_id'       => $this->resolveActorId(),
            ]);

        } catch (Throwable $e) {
            $this->error("❌ Erreur lors de l'attribution admin : {$e->getMessage()}");

            Log::error('Erreur attribution permissions admin', [
                'error'    => $e->getMessage(),
                'actor_id' => $this->resolveActorId(),
            ]);
        }
    }

    // ============================================================
    // INVALIDATION DES CACHES
    // ============================================================

    /**
     * Vide les caches globaux des permissions.
     */
    private function flushPermissionsCache(): void
    {
        Cache::forget(self::CACHE_PERMISSIONS);
        Cache::forget(self::CACHE_RESOURCES);
    }

    /**
     * ✅ Invalide les caches de TOUS les utilisateurs.
     *
     * Opération lourde mais nécessaire quand les permissions changent globalement
     * (création/mise à jour massive de permissions).
     *
     * ✅ CORRECTION #1 : utilise désormais le BON préfixe
     *    (`user_all_permissions_`) qui correspond à celui utilisé par
     *    `User::getAllPermissionNames()` et par les 2 controllers.
     */
    private function invalidateAllUserCaches(): void
    {
        $count = 0;

        User::query()
            ->select('id')
            ->chunkById(500, function ($users) use (&$count): void {
                foreach ($users as $user) {
                    $this->forgetUserCache((int) $user->id);
                    $count++;
                }
            });

        if ($count > 0) {
            $this->line("  🧹 <fg=gray>{$count} cache(s) utilisateur invalidé(s).</>");
        }
    }

    /**
     * ✅ Invalide uniquement les caches d'une liste d'IDs utilisateurs.
     *
     * Utilisé par assignAllPermissionsToAdmin() pour cibler précisément
     * les admins touchés sans parcourir toute la table users.
     */
    private function invalidateUserCachesForIds(array $userIds): void
    {
        if (empty($userIds)) {
            return;
        }

        foreach (array_unique($userIds) as $userId) {
            $this->forgetUserCache((int) $userId);
        }
    }

    /**
     * ✅ Point unique de suppression des clés de cache d'un utilisateur.
     *
     * Centralise le préfixe actuel + nettoie les anciennes clés legacy
     * (à supprimer après une période de transition en prod).
     */
    private function forgetUserCache(int $userId): void
    {
        // Clé actuelle (celle utilisée par User::getAllPermissionNames)
        Cache::forget(self::CACHE_USER_PERMS_PREFIX . $userId);

        // 🧹 Legacy — à retirer dans une future version
        Cache::forget("user_permissions_{$userId}");
        Cache::forget("user_roles_{$userId}");
    }

    // ============================================================
    // HELPERS
    // ============================================================

    /**
     * Récupère l'ID de l'utilisateur qui exécute la commande (si via un contrôleur).
     */
    private function resolveActorId(): ?int
    {
        return auth()->id();
    }

    /**
     * Extrait la ressource et l'action d'un nom de permission.
     *
     * @return array{0: string, 1: string}
     */
    private function extractResourceAndAction(string $permName): array
    {
        $parts = explode('.', $permName, 2);

        if (count($parts) < 2) {
            return ['', ''];
        }

        [$resource, $action] = $parts;

        return [trim($resource), trim($action)];
    }

    /**
     * Génère un label lisible pour la permission.
     */
    private function generateLabel(string $resource, string $action): string
    {
        $actionLabel = self::ACTION_LABELS[$action]
            ?? ucfirst(str_replace(['-', '_', '.'], ' ', $action));

        $resourceLabel = ucfirst(str_replace(['-', '_'], ' ', $resource));

        return "{$resourceLabel} — {$actionLabel}";
    }

    /**
     * Parse l'option --only en tableau de ressources.
     *
     * @return array<int, string>|null
     */
    private function resolveOnlyFilter(): ?array
    {
        $raw = $this->option('only');

        if (empty($raw)) {
            return null;
        }

        $filtered = array_filter(
            array_map('trim', explode(',', $raw)),
            fn ($v) => $v !== ''
        );

        if (empty($filtered)) {
            $this->warn('⚠️  --only est vide après filtrage, ignoré.');
            return null;
        }

        return array_values($filtered);
    }

    /**
     * ✅ Batch update des permissions via CASE WHEN (1 requête au lieu de N).
     *
     * @param array<int, array{id: int, resource?: string, action?: string, label?: string, updated_at: \DateTimeInterface}>
     */
    private function batchUpdatePermissions(array $updates): void
    {
        if (empty($updates)) {
            return;
        }

        // Regroupe les updates par combinaison de colonnes modifiées
        // pour ne faire que quelques UPDATE au lieu de N
        $byColumns = [];

        foreach ($updates as $row) {
            $id = $row['id'];
            unset($row['id']);

            $signature = implode(',', array_keys($row));
            $byColumns[$signature][] = ['id' => $id] + $row;
        }

        foreach ($byColumns as $rows) {
            $columns = array_diff(array_keys($rows[0]), ['id']);
            $ids     = array_column($rows, 'id');

            $caseClauses = [];
            $bindings    = [];

            foreach ($columns as $column) {
                $cases = [];
                foreach ($rows as $row) {
                    $cases[]    = 'WHEN id = ? THEN ?';
                    $bindings[] = $row['id'];
                    $bindings[] = $row[$column];
                }
                $caseClauses[] = "{$column} = CASE " . implode(' ', $cases) . ' END';
            }

            $idPlaceholders = implode(',', array_fill(0, count($ids), '?'));
            $bindings       = array_merge($bindings, $ids);

            $sql = 'UPDATE permissions SET '
                 . implode(', ', $caseClauses)
                 . " WHERE id IN ({$idPlaceholders})";

            DB::update($sql, $bindings);
        }
    }
}