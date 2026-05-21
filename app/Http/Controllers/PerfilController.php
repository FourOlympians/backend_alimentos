<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * PerfilController
 *
 * Requiere middleware supabase.jwt en las rutas.
 *
 * GET  /api/perfil        → devuelve perfil + condiciones activas del usuario autenticado
 * PUT  /api/perfil        → actualiza peso, talla, fecha_nacimiento, sexo
 */
class PerfilController extends Controller
{
    // ── GET /api/perfil ───────────────────────────────────────────────────

    public function show(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('supabase_user')->sub;

        $perfil = DB::table('profiles')
            ->where('id', $userId)
            ->first();

        if (! $perfil) {
            return response()->json(['error' => 'Perfil no encontrado.'], 404);
        }

        $condiciones = DB::table('usuario_condiciones as uc')
            ->join('condiciones_medicas as cm', 'cm.id', '=', 'uc.condicion_id')
            ->where('uc.usuario_id', $userId)
            ->where('uc.activa', true)
            ->select('cm.id', 'cm.clave', 'cm.nombre', 'cm.icono', 'uc.fecha_inicio')
            ->get();

        return response()->json([
            'perfil'      => $perfil,
            'condiciones' => $condiciones,
        ]);
    }

    // ── PUT /api/perfil ───────────────────────────────────────────────────

    public function update(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('supabase_user')->sub;

        $data = $request->validate([
            'nombre'           => 'sometimes|string|max:255',
            'fecha_nacimiento' => 'sometimes|date',
            'sexo'             => 'sometimes|in:M,F,O',
            'peso_kg'          => 'sometimes|numeric|min:20|max:300',
            'talla_cm'         => 'sometimes|numeric|min:50|max:250',
        ]);

        $data['updated_at'] = now();

        DB::table('profiles')
            ->updateOrInsert(['id' => $userId], $data);

        return response()->json(['message' => 'Perfil actualizado.']);
    }

    // ── POST /api/perfil/condiciones ──────────────────────────────────────
    // Sincroniza condiciones activas del usuario (reemplaza lista completa)

    public function syncCondiciones(Request $request): JsonResponse
    {
        $userId = $request->attributes->get('supabase_user')->sub;

        $request->validate([
            'condicion_ids'   => 'required|array',
            'condicion_ids.*' => 'integer|exists:condiciones_medicas,id',
        ]);

        // Desactiva todas
        DB::table('usuario_condiciones')
            ->where('usuario_id', $userId)
            ->update(['activa' => false]);

        // Reactiva o inserta las nuevas
        foreach ($request->condicion_ids as $condId) {
            DB::table('usuario_condiciones')->updateOrInsert(
                ['usuario_id' => $userId, 'condicion_id' => $condId],
                ['activa' => true, 'fecha_inicio' => now()->toDateString()]
            );
        }

        return response()->json(['message' => 'Condiciones actualizadas.']);
    }
}
