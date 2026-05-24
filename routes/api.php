<?php

use App\Http\Controllers\AlimentoController;
use App\Http\Controllers\CondicionController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\RecetaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| FoodLight API Routes
|--------------------------------------------------------------------------
|
| Rutas públicas (sin autenticación)
|   → catálogos: alimentos, grupos, condiciones, recetas
|
| Rutas protegidas (requieren Bearer token de Supabase)
|   → perfil del usuario, condiciones del usuario
|
| IMPORTANTE: Las rutas con segmentos fijos (ej. /semaforo, /para-mi)
| DEBEN ir ANTES de las rutas con parámetros dinámicos (ej. /{id}).
| De lo contrario Laravel interpreta "semaforo" como valor de {id}.
|
*/

// ── Públicas ──────────────────────────────────────────────────────────────────

Route::prefix('alimentos')->group(function () {
    Route::get('/',         [AlimentoController::class, 'index']);    // GET /api/alimentos
    Route::get('/semaforo', [AlimentoController::class, 'semaforo']); // GET /api/alimentos/semaforo  ← ANTES de /{id}
    Route::get('/{id}',     [AlimentoController::class, 'show']);     // GET /api/alimentos/{id}
});

Route::get('/grupos',      [AlimentoController::class,  'grupos']); // GET /api/grupos
Route::get('/condiciones', [CondicionController::class, 'index']);  // GET /api/condiciones

Route::prefix('recetas')->group(function () {
    Route::get('/',        [RecetaController::class, 'index']);   // GET /api/recetas
    Route::get('/para-mi', [RecetaController::class, 'paraMi']); // GET /api/recetas/para-mi  ← ANTES de /{id}
    Route::get('/{id}',    [RecetaController::class, 'show']);    // GET /api/recetas/{id}
});

// ── Protegidas (JWT Supabase) ──────────────────────────────────────────────────

Route::middleware('supabase.jwt')->group(function () {
    Route::get('/perfil',                  [PerfilController::class, 'show']);            // GET  /api/perfil
    Route::put('/perfil',                  [PerfilController::class, 'update']);          // PUT  /api/perfil
    Route::post('/perfil/condiciones',     [PerfilController::class, 'syncCondiciones']); // POST /api/perfil/condiciones
});

// ── Health-check ──────────────────────────────────────────────────────────────

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'app'    => config('app.name'),
    'env'    => config('app.env'),
]));