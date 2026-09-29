<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Vérifie que l'utilisateur possède au moins l'une des permissions données.
     *
     * Exemple d'utilisation : ->middleware('permission:cours.index,notes.saisie')
     */
    public function handle(Request $request, Closure $next, string $permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Non authentifié.');
        }

        // Transformer la chaîne "perm1,perm2" en tableau
        $permissionsArray = array_map('trim', explode(',', $permissions));

        foreach ($permissionsArray as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'Vous n\'avez pas la permission nécessaire.');
    }
}