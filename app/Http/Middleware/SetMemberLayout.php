<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class SetMemberLayout
{
    public function handle(Request $request, Closure $next)
    {
        // Indiquer aux vues qu'on est dans l'espace membre
        View::share('layout', 'layouts.member');
        return $next($request);
    }
}