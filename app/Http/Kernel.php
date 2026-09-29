<?php

declare(strict_types=1);

namespace App\Http;

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckRoutePermission;
use App\Http\Middleware\EncryptCookies;
use App\Http\Middleware\LogRouteAccess;
use App\Http\Middleware\PreventRequestsDuringMaintenance;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Http\Middleware\TrimStrings;
use App\Http\Middleware\TrustProxies;
use App\Http\Middleware\ValidateSignature;
use App\Http\Middleware\VerifyCsrfToken;
use Illuminate\Auth\Middleware\AuthenticateWithBasicAuth;
use Illuminate\Auth\Middleware\AuthenticateSession;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Auth\Middleware\RequirePassword;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\SetCacheHeaders;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class Kernel extends HttpKernel
{
    // ============================================================
    // MIDDLEWARES GLOBAUX
    // ============================================================
    // Exécutés sur CHAQUE requête (web + api + console).
    // Ordre critique : TrustProxies → HandleCors → Maintenance → ...
    // ============================================================

    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class, // ← Active si tu utilises des sous-domaines
        TrustProxies::class,
        HandleCors::class,
        PreventRequestsDuringMaintenance::class,
        ValidatePostSize::class,
        TrimStrings::class,
        ConvertEmptyStringsToNull::class,
    ];

    // ============================================================
    // GROUPES DE MIDDLEWARES
    // ============================================================
    // Groupes appliqués via ->middleware('web') / ->middleware('api')
    // ============================================================

    protected $middlewareGroups = [
        'web' => [
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,                          // ⚠️ AVANT auth
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,                    // ⚠️ APRÈS session
            // 🎯 Note : 'auth' et 'permission.route' ne sont PAS ici
            //    → appliqués explicitement sur les groupes de routes admin/membre
        ],

        'api' => [
            // \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            ThrottleRequests::class . ':api',
            SubstituteBindings::class,
        ],
    ];

    // ============================================================
    // ALIAS DE MIDDLEWARES
    // ============================================================
    // Utilisables via ->middleware('alias') ou ->middleware('alias:param')
    // ============================================================

    protected $middlewareAliases = [
        // ─── Laravel natifs ─────────────────────────────────────
        'auth'             => Authenticate::class,
        'auth.basic'       => AuthenticateWithBasicAuth::class,
        'auth.session'     => AuthenticateSession::class,
        'cache.headers'    => SetCacheHeaders::class,
        'can'              => Authorize::class,
        'guest'            => RedirectIfAuthenticated::class,
        'password.confirm' => RequirePassword::class,
        'signed'           => ValidateSignature::class,
        'throttle'         => ThrottleRequests::class,
        'verified'         => EnsureEmailIsVerified::class,

        // ─── Middlewares personnalisés ──────────────────────────
        'role'             => CheckRole::class,             // 🔒 Vérifie un ou plusieurs rôles
        'permission'       => CheckPermission::class,       // 🔒 Vérifie des permissions explicites
        'permission.route' => CheckRoutePermission::class,  // 🎯 Auto-dérivées du nom de route
        'log.route'        => LogRouteAccess::class,        // 📊 Journalisation des accès
    ];

    // ============================================================
    // ORDRE DE PRIORITÉ DES MIDDLEWARES
    // ============================================================
    // ⚠️ CRITIQUE : force l'ordre peu importe l'ordre de déclaration
    //    sur les routes. Sans ça, 'permission.route' peut tourner
    //    AVANT 'auth' → $request->user() = null → faux 403.
    //
    // Ordre logique :
    //   1. Session (nécessaire pour connaître l'utilisateur connecté)
    //   2. Auth (charge $request->user())
    //   3. Vérifications de permissions/rôles (dépendent de auth)
    //   4. Bindings (résolution des modèles liés aux routes)
    //   5. Authorize (policies — après les bindings)
    // ============================================================

    protected $middlewarePriority = [
        // ─── Infrastructure de base ──────────────────────────────
        HandlePrecognitiveRequests::class,
        EncryptCookies::class,
        StartSession::class,
        ShareErrorsFromSession::class,

        // ─── Authentification ────────────────────────────────────
        \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class, // = 'auth'
        AuthenticateSession::class,

        // ─── ✅ Vérifications de sécurité (APRÈS auth) ───────────
        CheckRoutePermission::class,   // 🎯 Auto-adaptatif (permission.route)
        CheckRole::class,              // 🔒 Rôles (role)
        CheckPermission::class,        // 🔒 Permissions explicites (permission)

        // ─── Rate limiting ───────────────────────────────────────
        ThrottleRequests::class,
        ThrottleRequestsWithRedis::class,

        // ─── Bindings + Policies (en dernier) ────────────────────
        SubstituteBindings::class,
        Authorize::class,
    ];
}