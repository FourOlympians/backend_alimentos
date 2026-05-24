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

        $claves = \App\Models\CondicionMedica::whereIn('id', $condicionIds)->pluck('clave')->toArray();

        $resultado = 'verde';

        // 1. Regla estricta para Celiaquía (gluten es totalmente prohibitivo)
        if (in_array('celiaquia', $claves)) {
            if ($alimento->contiene_gluten) {
                $resultado = 'rojo';
            }
        }

        // 2. Cargar y aplicar las reglas dinámicas de la base de datos (Diabetes, Hipertensión)
        $reglas = ReglaSemaforo::whereIn('condicion_id', $condicionIds)
            ->orderBy('prioridad', 'desc')
            ->get();

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
     * Clasifica sin condiciones usando heurísticas nutricionales básicas basadas en el SMAE.
     */
    protected function clasificacionGeneral(Alimento $alimento): string
    {
        $sodio     = (float) ($alimento->sodio_mg       ?? 0);
        $azucar    = (float) ($alimento->azucar_g       ?? 0);
        $satGrasas = (float) ($alimento->ag_saturados_g ?? 0);

        if ($sodio > 350 || $azucar > 10 || $satGrasas > 4) {
            return 'rojo';
        }
        if ($sodio > 120 || $azucar > 5 || $satGrasas > 1.5) {
            return 'amarillo';
        }

        return 'verde';
    }

    protected function peorColor(string $actual, string $nueva): string
    {
        $orden = ['verde' => 0, 'amarillo' => 1, 'rojo' => 2];
        return ($orden[$nueva] ?? 0) > ($orden[$actual] ?? 0) ? $nueva : $actual;
    }
}
