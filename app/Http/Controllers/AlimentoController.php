<?php

namespace App\Http\Controllers;

use App\Models\Alimento;
use App\Models\GrupoAlimento;
use App\Services\SemaforoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AlimentoController
 *
 * GET  /api/alimentos              → lista paginada con búsqueda y filtros
 * GET  /api/alimentos/{id}         → detalle
 * GET  /api/alimentos/semaforo     → lista con color asignado según condiciones del usuario
 * GET  /api/grupos                 → lista de grupos
 */
class AlimentoController extends Controller
{
    public function __construct(protected SemaforoService $semaforo) {}

    // ── GET /api/alimentos ────────────────────────────────────────────────

    public function index(Request $request): JsonResponse
    {
        $query = Alimento::with('grupo')
            ->search($request->query('q'))
            ->delGrupo($request->query('grupo_id'));

        $perPage = min((int) ($request->query('per_page', 20)), 100);
        $result  = $query->paginate($perPage);

        return response()->json($result);
    }

    // ── GET /api/alimentos/{id} ───────────────────────────────────────────

    public function show(int $id): JsonResponse
    {
        $alimento = Alimento::with('grupo')->findOrFail($id);
        return response()->json($alimento);
    }

    // ── GET /api/alimentos/semaforo ───────────────────────────────────────
    //
    // Query params:
    //   condicion_ids  = "1,2,3"   IDs separados por coma
    //   q              = término de búsqueda
    //   grupo_id       = filtro de grupo
    //   color          = verde | amarillo | rojo
    //   per_page       = 20 (default)

    public function semaforo(Request $request): JsonResponse
    {
        $condicionIds = array_filter(
            explode(',', $request->query('condicion_ids', '')),
            fn ($v) => is_numeric($v)
        );
        $condicionIds = array_map('intval', $condicionIds);

        $alimentos = Alimento::with('grupo')
            ->search($request->query('q'))
            ->delGrupo($request->query('grupo_id'))
            ->get();

        $coloresFiltro = $request->query('color');

        $resultado = $alimentos
            ->map(function (Alimento $a) use ($condicionIds) {
                $color = $this->semaforo->clasificar($a, $condicionIds);
                return array_merge($a->toArray(), [
                    'color'       => $color,
                    'grupo_nombre' => $a->grupo?->nombre ?? 'Otro',
                ]);
            })
            ->when($coloresFiltro, fn ($c) => $c->where('color', $coloresFiltro))
            ->values();

        return response()->json([
            'data'   => $resultado,
            'totals' => [
                'verde'    => $resultado->where('color', 'verde')->count(),
                'amarillo' => $resultado->where('color', 'amarillo')->count(),
                'rojo'     => $resultado->where('color', 'rojo')->count(),
            ],
        ]);
    }

    // ── GET /api/grupos ────────────────────────────────────────────────────

    public function grupos(): JsonResponse
    {
        return response()->json(GrupoAlimento::orderBy('nombre')->get());
    }
}
