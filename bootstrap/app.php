<?php

use App\Http\Middleware\VerifySupabaseJwt;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        apiPrefix: 'api',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Alias para usar 'supabase.jwt' en las rutas
        $middleware->alias([
            'supabase.jwt' => VerifySupabaseJwt::class,
        ]);

        // CORS para el frontend Vue
        $middleware->api(prepend: [
            \Illuminate\Http\Middleware\HandleCors::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Respuesta JSON uniforme para errores de validación
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            return response()->json([
                'error'   => 'Error de validación.',
                'detalles' => $e->errors(),
            ], 422);
        });

        // 404 en JSON
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso no encontrado.'], 404);
        });
    })->create();
