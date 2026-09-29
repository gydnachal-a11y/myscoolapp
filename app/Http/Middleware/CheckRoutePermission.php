<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * ✅ Middleware auto-adaptatif : dérive la permission du nom de route.
 *
 *   admin.eleves.index       → eleves.index
 *   admin.roles.sync-users   → roles.sync-users
 *   admin.paiements.destroy  → paiements.destroy
 *
 * Compatible avec CheckPermission existant : utilise $user->hasPermission().
 */
class CheckRoutePermission
{
    private const ADMIN_PREFIX = 'admin.';

    /**
     * Routes admin sans permission requise (dashboard, redirections…).
     */
    private const ROUTES_SANS_PERMISSION = [
        'admin.home',
        'admin.redirect',
        'admin.logout',
        'admin.dashboard',
    ];

    /**
     * Rôles bypassant la vérification.
     */
    private const SUPER_ROLES = ['super_admin'];

    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (!$route) {
            return $next($request);
        }

        $routeName = $route->getName();

        // Pas de nom, pas admin, ou exemptée → laisse passer
        if (
            !$routeName
            || !str_starts_with($routeName, self::ADMIN_PREFIX)
            || in_array($routeName, self::ROUTES_SANS_PERMISSION, true)
        ) {
            return $next($request);
        }

        $user = $request->user('web');

        if (!$user) {
            // Laisse le middleware 'auth' gérer la redirection login
            return $next($request);
        }

        // Bypass super admin
        if ($this->isSuperUser($user)) {
            return $next($request);
        }

        // Dériver : admin.eleves.index → eleves.index
        $permission = substr($routeName, strlen(self::ADMIN_PREFIX));

        // ✅ Même contrat que CheckPermission
        if (!$user->hasPermission($permission)) {
            $this->logUnauthorizedAccess($user, $routeName, $permission);

            abort(403, "Vous n'avez pas la permission d'accéder à cette ressource ({$permission}).");
        }

        return $next($request);
    }

    private function isSuperUser($user): bool
    {
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        foreach (self::SUPER_ROLES as $role) {
            if (method_exists($user, 'hasRole') && $user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    private function logUnauthorizedAccess($user, string $routeName, string $permission): void
    {
        Log::warning('Accès refusé — permission manquante', [
            'user_id'    => $user->id ?? null,
            'user_email' => $user->email ?? null,
            'route'      => $routeName,
            'permission' => $permission,
            'ip'         => request()->ip(),
            'url'        => request()->fullUrl(),
        ]);
    }
}