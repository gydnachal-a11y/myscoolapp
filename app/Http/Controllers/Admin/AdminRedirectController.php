<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class AdminRedirectController extends Controller
{
    /**
     * Redirige l'utilisateur connecté vers le bon tableau de bord.
     * Remplace la closure initialement définie dans routes/web.php.
     */
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $isAdmin = $user->isSuperAdmin()
            || $user->hasRole('admin')
            || (method_exists($user, 'hasPermissionLike')
                && $user->hasPermissionLike('admin.%'));

        return redirect()->route(
            $isAdmin ? 'admin.statistiques.index' : 'dashboard.index'
        );
    }
}