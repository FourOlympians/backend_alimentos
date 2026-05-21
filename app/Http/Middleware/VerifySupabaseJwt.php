<?php

namespace App\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * VerifySupabaseJwt
 *
 * Valida el Bearer token emitido por Supabase Auth.
 * Si el token es válido, inyecta los claims en $request->supabase_user.
 *
 * Uso en rutas:
 *   Route::middleware('supabase.jwt')->group(...)
 */
class VerifySupabaseJwt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json(['error' => 'Token no proporcionado.'], 401);
        }

        try {
            $secret  = config('supabase.jwt_secret');
            // Supabase usa HS256 y el secret está en base64
            $decoded = JWT::decode($token, new Key(base64_decode($secret), 'HS256'));

            // Inyecta los claims para que los controladores los usen
            $request->attributes->set('supabase_user', $decoded);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Token inválido: ' . $e->getMessage()], 401);
        }

        return $next($request);
    }
}
