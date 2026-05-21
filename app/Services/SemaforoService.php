<?php

namespace App\Services;

use App\Models\Alimento;
use App\Models\ReglaSemaforo;
use Illuminate\Support\Collection;

/**
 * SemaforoService
 *
 * Clasifica un alimento (verde / amarillo / rojo) aplicando las reglas
 * almacenadas en la tabla reglas_semaforo para las condiciones activas del usuario.
 *
 * Prioridad: rojo > amarillo > verde
 */
class SemaforoService
{
    /**
     * @param  Alimento    $alimento
     * @param  int[]       $condicionIds  IDs de las condiciones activas del usuario
     * @return string      'verde' | 'amarillo' | 'rojo'
     */
    public function clasificar(Alimento $alimento, array $condicionIds): string
    {
        if (empty($condicionIds)) {
            return $this->clasificacionGeneral($alimento);
        }

        $reglas = ReglaSemaforo::whereIn('condicion_id', $condicionIds)
            ->orderBy('prioridad', 'desc')
            ->get();

        $resultado = 'verde';

        foreach ($reglas as $regla) {
            $valor = $alimento->{$regla->campo_nutriente} ?? null;

            if ($valor === null) continue;

            $valor = (float) $valor;

            $cumpleMin = $regla->umbral_min === null || $valor >= (float) $regla->umbral_min;
            $cumpleMax = $regla->umbral_max === null || $valor <= (float) $regla->umbral_max;

            if ($cumpleMin && $cumpleMax) {
                $resultado = $this->peorColor($resultado, $regla->color);
            }
        }

        return $resultado;
    }

    /**
     * Clasifica sin condiciones usando heurísticas nutricionales básicas.
     */
    protected function clasificacionGeneral(Alimento $alimento): string
    {
        $fibra  = (float) ($alimento->fibra_g       ?? 0);
        $kcal   = (float) ($alimento->energia_kcal  ?? 0);
        $sodio  = (float) ($alimento->sodio_mg      ?? 0);
        $azucar = (float) ($alimento->azucar_g      ?? 0);

        if ($sodio > 400 || $azucar > 20) return 'rojo';
        if ($kcal  > 300 || $sodio > 150) return 'amarillo';
        if ($fibra  > 3)                  return 'verde';

        return 'verde';
    }

    protected function peorColor(string $actual, string $nueva): string
    {
        $orden = ['verde' => 0, 'amarillo' => 1, 'rojo' => 2];
        return ($orden[$nueva] ?? 0) > ($orden[$actual] ?? 0) ? $nueva : $actual;
    }
}
