<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidarApiToken
{
    /**
     * Valida que la petición incluya el token compartido de servicio a servicio.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Api-Token');

        if (! $token || ! hash_equals((string) config('services.api_token'), (string) $token)) {
            return response()->json([
                'message' => 'No autorizado.',
            ], 401);
        }

        return $next($request);
    }
}
