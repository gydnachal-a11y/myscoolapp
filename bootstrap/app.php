<?php

declare(strict_types=1);

use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\CheckRole;
use App\Http\Middleware\CheckRoutePermission;
use App\Http\Middleware\LogRouteAccess;
use App\Http\Middleware\SetMemberLayout;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\AuthenticateSession;
use Illuminate\Auth\Middleware\Authorize;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))

    // ============================================================
    // ROUTING
    // ============================================================
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )

    // ============================================================
    // MIDDLEWARE
    // ============================================================
    ->withMiddleware(function (Middleware $middleware): void {

        // ------------------------------------------------------------
        // ALIASES
        // ------------------------------------------------------------
        $middleware->alias([
            'role'             => CheckRole::class,
            'permission'       => CheckPermission::class,
            'permission.route' => CheckRoutePermission::class,
            'member.layout'    => SetMemberLayout::class,
            'log.route'        => LogRouteAccess::class,
        ]);

        // ------------------------------------------------------------
        // PRIORITÉ — force l'ordre auth AVANT permission.route
        // ------------------------------------------------------------
        $middleware->priority([
            HandlePrecognitiveRequests::class,
            EncryptCookies::class,
            StartSession::class,
            ShareErrorsFromSession::class,

            \Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests::class,
            AuthenticateSession::class,

            CheckRoutePermission::class,
            CheckRole::class,
            CheckPermission::class,

            ThrottleRequests::class,
            ThrottleRequestsWithRedis::class,

            SubstituteBindings::class,
            Authorize::class,
        ]);

        // ------------------------------------------------------------
        // CSRF exemptions (webhooks externes)
        // ⚠️ Chaque webhook DOIT vérifier sa signature dans le contrôleur
        // ------------------------------------------------------------
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/webhooks/*',
        ]);

        // ------------------------------------------------------------
        // Trusted proxies (Laravel Cloud = derrière un LB)
        // On précise les headers pour éviter le spoofing d'IP/proto
        // ------------------------------------------------------------
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_AWS_ELB,
        );

        // ------------------------------------------------------------
        // REDIRECTIONS
        // ------------------------------------------------------------
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('admin.redirect'));
    })

    // ============================================================
    // EXCEPTIONS
    // ============================================================
    ->withExceptions(function (Exceptions $exceptions): void {

        // ------------------------------------------------------------
        // JSON automatique pour API / AJAX
        // ------------------------------------------------------------
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );

        // ------------------------------------------------------------
        // Ne pas rapporter (bénin, géré par Laravel)
        // ------------------------------------------------------------
        $exceptions->dontReport([
            ValidationException::class,
            TokenMismatchException::class,
            AuthenticationException::class,
        ]);

        // ------------------------------------------------------------
        // Éviter les logs dupliqués
        // ------------------------------------------------------------
        $exceptions->dontReportDuplicates();

        // ------------------------------------------------------------
        // Ne JAMAIS flasher les champs sensibles en session
        // ------------------------------------------------------------
        $exceptions->dontFlash([
            'password',
            'password_confirmation',
            'current_password',
            'token',
            'secret',
            'api_key',
        ]);

        // ============================================================
        // HELPER INTERNE
        // ============================================================

        /**
         * Rend une vue d'erreur avec $exception disponible + headers préservés.
         */
        $renderError = static function (
            Request $request,
            Throwable $e,
            int $status,
            string $fallback,
        ): Response {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $fallback,
                    'status'  => $status,
                ], $status);
            }

            $view    = "errors.{$status}";
            $headers = $e instanceof HttpExceptionInterface
                ? $e->getHeaders()
                : [];

            if (View::exists($view)) {
                return response()
                    ->view($view, ['exception' => $e], $status)
                    ->withHeaders($headers);
            }

            return response($fallback, $status)->withHeaders($headers);
        };

        // ============================================================
        // HANDLERS SPÉCIFIQUES (ordre = priorité)
        // ============================================================

        // ------------------------------------------------------------
        // ✅ 422 — Erreur de validation de formulaire
        //
        // C'EST LE FIX PRINCIPAL : sans ce handler, ValidationException
        // tombe dans le catch-all Throwable, qui la transforme en 500
        // et la log comme erreur critique. Ici, on laisse Laravel
        // gérer le redirect + $errors en session.
        // ------------------------------------------------------------
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                    'errors'  => $e->errors(),
                    'status'  => 422,
                ], 422);
            }

            // ✅ null = Laravel garde son comportement par défaut :
            //    redirect back() + $errors + old() input
            return null;
        });

        // ------------------------------------------------------------
        // 401 — Non authentifié
        // ------------------------------------------------------------
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Non authentifié.',
                    'status'  => 401,
                ], 401);
            }

            return redirect()->guest(route('login'));
        });

        // ------------------------------------------------------------
        // 403 — Accès non autorisé
        // ------------------------------------------------------------
        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) use ($renderError) {
            return $renderError($request, $e, 403, 'Accès non autorisé.');
        });

        // ------------------------------------------------------------
        // 404 — Ressource introuvable
        // ------------------------------------------------------------
        $exceptions->render(function (
            NotFoundHttpException|ModelNotFoundException $e,
            Request $request,
        ) use ($renderError) {
            return $renderError($request, $e, 404, 'Page introuvable.');
        });

        // ------------------------------------------------------------
        // 405 — Méthode HTTP non autorisée
        // ------------------------------------------------------------
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) use ($renderError) {
            return $renderError($request, $e, 405, 'Méthode HTTP non autorisée.');
        });

        // ------------------------------------------------------------
        // 419 — Session expirée (CSRF token mismatch)
        // ------------------------------------------------------------
        $exceptions->render(function (TokenMismatchException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Session expirée. Rechargez la page.',
                    'status'  => 419,
                ], 419);
            }

            $previous = url()->previous() ?: route('home');

            return redirect($previous)
                ->withInput($request->except(['password', 'password_confirmation', 'token']))
                ->with('error', 'Votre session a expiré. Veuillez réessayer.');
        });

        // ------------------------------------------------------------
        // 429 — ThrottleRequestsException (middleware throttle:)
        // ------------------------------------------------------------
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) use ($renderError) {
            return $renderError(
                $request,
                $e,
                429,
                'Trop de requêtes. Veuillez patienter quelques instants.',
            );
        });

        // ------------------------------------------------------------
        // 429 — TooManyRequestsHttpException (abort(429))
        // ------------------------------------------------------------
        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) use ($renderError) {
            return $renderError(
                $request,
                $e,
                429,
                'Trop de requêtes. Veuillez patienter quelques instants.',
            );
        });

        // ------------------------------------------------------------
        // 5xx — Erreurs HTTP génériques (500, 503…)
        // ------------------------------------------------------------
        $exceptions->render(function (HttpException $e, Request $request) use ($renderError) {
            $statusCode = $e->getStatusCode();

            if ($statusCode >= 500) {
                Log::error("Erreur HTTP {$statusCode}", [
                    'url'     => $request->fullUrl(),
                    'method'  => $request->method(),
                    'message' => $e->getMessage(),
                    'user_id' => auth()->id(),
                    'ip'      => $request->ip(),
                ]);
            }

            $fallback = $e->getMessage() ?: "Erreur {$statusCode}";

            return $renderError($request, $e, $statusCode, $fallback);
        });

        // ------------------------------------------------------------
        // ✅ Catch-all pour les erreurs 500 VRAIMENT non-HttpException
        //
        // On EXCLUT explicitement les exceptions "métier" pour éviter
        // qu'elles soient loggées comme erreurs critiques ou
        // transformées en 500 alors que Laravel sait les gérer.
        // ------------------------------------------------------------
        $exceptions->render(function (Throwable $e, Request $request) use ($renderError) {

            // ⛔ Exceptions gérées par Laravel lui-même :
            //    - HttpExceptionInterface  → déjà gérée plus haut
            //    - ValidationException     → redirect + $errors
            //    - AuthenticationException → redirect login
            //    - TokenMismatchException  → redirect + message
            if (
                $e instanceof HttpExceptionInterface
                || $e instanceof ValidationException
                || $e instanceof AuthenticationException
                || $e instanceof TokenMismatchException
            ) {
                return null;
            }

            Log::error('Exception non-HttpException', [
                'url'     => $request->fullUrl(),
                'method'  => $request->method(),
                'message' => $e->getMessage(),
                'class'   => get_class($e),
                'user_id' => auth()->id(),
            ]);

            return $renderError(
                $request,
                $e,
                500,
                'Une erreur inattendue est survenue. Nos équipes ont été notifiées.',
            );
        });
    })

    ->create();