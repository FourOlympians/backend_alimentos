<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Condiciones médicas ────────────────────────────────────────────
        $condiciones = [
            ['clave' => 'diabetes',      'nombre' => 'Diabetes',      'icono' => '🩸', 'descripcion' => 'Controla índice glucémico y azúcar.'],
            ['clave' => 'hipertension',  'nombre' => 'Hipertensión',  'icono' => '💊', 'descripcion' => 'Limita sodio y grasas saturadas.'],
            ['clave' => 'celiaquia',     'nombre' => 'Celiaquía',     'icono' => '🌾', 'descripcion' => 'Evita alimentos con gluten.'],
        ];

        foreach ($condiciones as $c) {
            DB::table('condiciones_medicas')->updateOrInsert(
                ['clave' => $c['clave']],
                array_merge($c, ['created_at' => now()])
            );
        }

        // ── Reglas semáforo ────────────────────────────────────────────────
        $diabetesId     = DB::table('condiciones_medicas')->where('clave', 'diabetes')->value('id');
        $hipertensionId = DB::table('condiciones_medicas')->where('clave', 'hipertension')->value('id');
        $celiaquiaId    = DB::table('condiciones_medicas')->where('clave', 'celiaquia')->value('id');

        $reglas = [
            // Diabetes – Índice Glucémico (IG) bajo el SMAE
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'indice_glucemico', 'color' => 'rojo',     'umbral_min' => 70.00, 'umbral_max' => null,  'descripcion' => 'Índice glucémico alto (>= 70)',    'prioridad' => 3],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'indice_glucemico', 'color' => 'amarillo', 'umbral_min' => 55.00, 'umbral_max' => 69.90, 'descripcion' => 'Índice glucémico medio (55-69)',   'prioridad' => 2],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'indice_glucemico', 'color' => 'verde',    'umbral_min' => null,  'umbral_max' => 54.90, 'descripcion' => 'Índice glucémico bajo (< 55)',     'prioridad' => 1],

            // Diabetes – Carga Glucémica (CG) bajo el SMAE
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'carga_glucemica', 'color' => 'rojo',     'umbral_min' => 20.00, 'umbral_max' => null,  'descripcion' => 'Carga glucémica alta (>= 20)',     'prioridad' => 3],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'carga_glucemica', 'color' => 'amarillo', 'umbral_min' => 11.00, 'umbral_max' => 19.90, 'descripcion' => 'Carga glucémica media (11-19)',    'prioridad' => 2],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'carga_glucemica', 'color' => 'verde',    'umbral_min' => null,  'umbral_max' => 10.90, 'descripcion' => 'Carga glucémica baja (<= 10)',     'prioridad' => 1],

            // Diabetes – Azúcar por ración (g) bajo el SMAE
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'azucar_g', 'color' => 'rojo',     'umbral_min' => 10.01, 'umbral_max' => null,  'descripcion' => 'Alto contenido de azúcar (> 10g)',   'prioridad' => 3],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'azucar_g', 'color' => 'amarillo', 'umbral_min' => 5.01,  'umbral_max' => 10.00, 'descripcion' => 'Moderado contenido de azúcar (5-10g)', 'prioridad' => 2],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'azucar_g', 'color' => 'verde',    'umbral_min' => null,  'umbral_max' => 5.00,  'descripcion' => 'Bajo contenido de azúcar (<= 5g)',   'prioridad' => 1],

            // Hipertensión – Sodio (mg) bajo el SMAE / NOM-086
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'sodio_mg', 'color' => 'rojo',     'umbral_min' => 350.01, 'umbral_max' => null,   'descripcion' => 'Alto contenido de sodio (> 350mg)',   'prioridad' => 3],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'sodio_mg', 'color' => 'amarillo', 'umbral_min' => 120.01, 'umbral_max' => 350.00, 'descripcion' => 'Moderado contenido de sodio (120-350mg)', 'prioridad' => 2],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'sodio_mg', 'color' => 'verde',    'umbral_min' => null,   'umbral_max' => 120.00, 'descripcion' => 'Bajo contenido de sodio (<= 120mg)',   'prioridad' => 1],

            // Hipertensión – Grasas Saturadas (g) bajo el SMAE
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'ag_saturados_g', 'color' => 'rojo',     'umbral_min' => 4.01, 'umbral_max' => null, 'descripcion' => 'Alto en grasas saturadas (> 4g)',    'prioridad' => 3],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'ag_saturados_g', 'color' => 'amarillo', 'umbral_min' => 1.51, 'umbral_max' => 4.00, 'descripcion' => 'Moderado en grasas saturadas (1.5-4g)', 'prioridad' => 2],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'ag_saturados_g', 'color' => 'verde',    'umbral_min' => null, 'umbral_max' => 1.50, 'descripcion' => 'Bajo en grasas saturadas (<= 1.5g)',  'prioridad' => 1],

            // Hipertensión – Colesterol (mg) bajo el SMAE
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'colesterol_mg', 'color' => 'rojo',     'umbral_min' => 50.01, 'umbral_max' => null,  'descripcion' => 'Alto contenido de colesterol (> 50mg)',   'prioridad' => 3],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'colesterol_mg', 'color' => 'amarillo', 'umbral_min' => 20.01, 'umbral_max' => 50.00, 'descripcion' => 'Moderado contenido de colesterol (20-50mg)', 'prioridad' => 2],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'colesterol_mg', 'color' => 'verde',    'umbral_min' => null,  'umbral_max' => 20.00, 'descripcion' => 'Bajo contenido de colesterol (<= 20mg)',   'prioridad' => 1],
        ];

        foreach ($reglas as $r) {
            DB::table('reglas_semaforo')->insert(array_merge($r, ['created_at' => now()]));
        }

        // Celiaquía no usa reglas numéricas; se maneja con el campo contiene_gluten (boolean).
        // El SemaforoService puede extenderse para soportarlo directamente.

        $this->command->info('✅ Condiciones y reglas de semáforo creadas.');
    }
}
