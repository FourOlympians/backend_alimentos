<?php

namespace App\Http\Controllers;

use App\Models\Receta;
use App\Models\CondicionMedica;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RecetaController
 *
 * GET  /api/recetas                → lista paginada
 * GET  /api/recetas/{id}           → detalle con ingredientes y sus colores
 * GET  /api/recetas/para-mi        → recetas filtradas por condiciones del usuario
 */
class RecetaController extends Controller
{
    // ── GET /api/recetas ──────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $recetas = Receta::where('activa', true)
            ->when($request->query('q'), fn ($q, $term) =>
                $q->where('nombre', 'ilike', "%{$term}%")
            )
            ->with(['ingredientes.alimento'])
            ->paginate(min((int) $request->query('per_page', 10), 50));

        return response()->json($recetas);
    }

    // ── GET /api/recetas/{id} ─────────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $receta = Receta::with([
            'ingredientes.alimento.grupo',
            'condiciones',
        ])->findOrFail($id);

        return response()->json($receta);
    }

    // ── GET /api/recetas/para-mi ─────────────────────────────────────────
    //
    // Filtra recetas cuyo color_promedio en receta_condiciones sea
    // verde o amarillo para las condiciones indicadas.
    //
    // Query params:
    //   condicion_ids = "1,2"
    //   tiempo_max    = máximo en minutos
    //   kcal_max      = máximo de calorías por porción (aprox.)

    public function paraMi(Request $request): JsonResponse
    {
        $condicionIds = array_filter(
            explode(',', $request->query('condicion_ids', '')),
            fn ($v) => is_numeric($v)
        );
        $condicionIds = array_map('intval', $condicionIds);

        $query = Receta::where('activa', true)
            ->with(['ingredientes.alimento', 'condiciones']);

        if (! empty($condicionIds)) {
            // Recetas que NO tienen rojo para ninguna de las condiciones del usuario
            $query->whereDoesntHave('condiciones', function ($q) use ($condicionIds) {
                $q->whereIn('condicion_id', $condicionIds)
                  ->where('color_promedio', 'rojo');
            });
        }

        if ($tiempoMax = $request->query('tiempo_max')) {
            $query->where('tiempo_min', '<=', (int) $tiempoMax);
        }

        return response()->json($query->paginate(10));
    }
}
