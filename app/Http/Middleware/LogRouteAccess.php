<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRouteAccess
{
    /**
     * Journalise l'accès aux routes (utile pour l'audit).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // On ne journalise que les requêtes GET, POST, PUT, PATCH, DELETE
        if (in_array($request->method(), ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'])) {
            Log::info('Accès route', [
                'user_id'    => auth()->id(),
                'user_name'  => auth()->user()?->name,
                'route'      => $request->route()?->getName(),
                'method'     => $request->method(),
                'path'       => $request->path(),
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now()->toDateTimeString(),
            ]);
        }

        return $response;
    }
}