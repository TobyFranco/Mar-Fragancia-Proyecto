<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $rol): Response
    {
        $usuario = auth()->user();

        if (!$usuario || $usuario->rol->nombre !== $rol) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

     return $next($request);
    }
}