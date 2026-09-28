<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (! $user || ! $user->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'No tiene permisos de administrador para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }
}
