<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verificar si el usuario está logueado
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // 2. Verificar si el usuario tiene alguno de los roles permitidos
        if (!in_array(auth()->user()->role->name, $roles)) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }

        return $next($request);
    }
}