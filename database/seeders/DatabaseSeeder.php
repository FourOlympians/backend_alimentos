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
        $celiaquia Id   = DB::table('condiciones_medicas')->where('clave', 'celiaquia')->value('id');

        $reglas = [
            // Diabetes – índice glucémico
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'indice_glucemico', 'color' => 'rojo',     'umbral_min' => 70,   'umbral_max' => null, 'descripcion' => 'IG alto',    'prioridad' => 3],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'indice_glucemico', 'color' => 'amarillo', 'umbral_min' => 55,   'umbral_max' => 69,   'descripcion' => 'IG medio',   'prioridad' => 2],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'indice_glucemico', 'color' => 'verde',    'umbral_min' => null, 'umbral_max' => 54,   'descripcion' => 'IG bajo',    'prioridad' => 1],
            // Diabetes – azúcar
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'azucar_g', 'color' => 'rojo',     'umbral_min' => 15,   'umbral_max' => null, 'descripcion' => 'Azúcar alta',  'prioridad' => 3],
            ['condicion_id' => $diabetesId, 'campo_nutriente' => 'azucar_g', 'color' => 'amarillo', 'umbral_min' => 8,    'umbral_max' => 14.9, 'descripcion' => 'Azúcar media', 'prioridad' => 2],
            // Hipertensión – sodio
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'sodio_mg', 'color' => 'rojo',     'umbral_min' => 400,  'umbral_max' => null, 'descripcion' => 'Sodio muy alto', 'prioridad' => 3],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'sodio_mg', 'color' => 'amarillo', 'umbral_min' => 150,  'umbral_max' => 399,  'descripcion' => 'Sodio alto',     'prioridad' => 2],
            ['condicion_id' => $hipertensionId, 'campo_nutriente' => 'sodio_mg', 'color' => 'verde',    'umbral_min' => null, 'umbral_max' => 149,  'descripcion' => 'Sodio bajo',     'prioridad' => 1],
        ];

        foreach ($reglas as $r) {
            DB::table('reglas_semaforo')->insert(array_merge($r, ['created_at' => now()]));
        }

        // Celiaquía no usa reglas numéricas; se maneja con el campo contiene_gluten (boolean).
        // El SemaforoService puede extenderse para soportarlo directamente.

        $this->command->info('✅ Condiciones y reglas de semáforo creadas.');
    }
}
