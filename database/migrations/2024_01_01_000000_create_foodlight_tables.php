<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración completa de FoodLight para Supabase / PostgreSQL.
 *
 * Ejecutar:  php artisan migrate
 *
 * Nota: Supabase ya gestiona auth.users. El FK desde profiles
 * hacia auth.users NO se puede crear vía Laravel (esquema diferente);
 * se gestiona directamente en Supabase.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1 ── grupos_alimentos ─────────────────────────────────────────────
        Schema::create('grupos_alimentos', function (Blueprint $t) {
            $t->id();
            $t->string('nombre')->unique();
            $t->text('descripcion')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // 2 ── alimentos ────────────────────────────────────────────────────
        Schema::create('alimentos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('grupo_id')->constrained('grupos_alimentos');
            $t->string('nombre');
            $t->string('nombre_normalizado')->nullable()
              ->comment('Generado automáticamente: lower(trim(nombre))');
            $t->string('cantidad_sugerida')->nullable();
            $t->string('unidad')->nullable();
            $t->decimal('peso_bruto_g',  8, 2)->nullable();
            $t->decimal('peso_neto_g',   8, 2)->nullable();
            $t->decimal('energia_kcal',  8, 2)->nullable();
            $t->decimal('proteina_g',    8, 2)->nullable();
            $t->decimal('lipidos_g',     8, 2)->nullable();
            $t->decimal('hidratos_carbono_g', 8, 2)->nullable();
            $t->decimal('ag_saturados_g',     8, 2)->nullable();
            $t->decimal('ag_monoinsaturados_g', 8, 2)->nullable();
            $t->decimal('ag_poliinsaturados_g', 8, 2)->nullable();
            $t->decimal('colesterol_mg',  8, 2)->nullable();
            $t->decimal('azucar_g',       8, 2)->nullable();
            $t->decimal('fibra_g',        8, 2)->nullable();
            $t->decimal('vitamina_a_mg_re', 8, 4)->nullable();
            $t->decimal('acido_ascorbico_mg', 8, 2)->nullable();
            $t->decimal('acido_folico_mg',    8, 4)->nullable();
            $t->decimal('calcio_mg',    8, 2)->nullable();
            $t->decimal('hierro_mg',    8, 2)->nullable();
            $t->decimal('potasio_mg',   8, 2)->nullable();
            $t->decimal('sodio_mg',     8, 2)->nullable();
            $t->decimal('fosforo_mg',   8, 2)->nullable();
            $t->decimal('etanol_g',     8, 2)->nullable();
            $t->decimal('indice_glucemico', 5, 1)->nullable();
            $t->decimal('carga_glucemica',  5, 1)->nullable();
            $t->boolean('contiene_gluten')->default(false);
            $t->timestampsTz();
        });

        // Trigger para nombre_normalizado (PostgreSQL)
        \DB::unprepared("
            CREATE OR REPLACE FUNCTION set_nombre_normalizado()
            RETURNS TRIGGER AS \$\$
            BEGIN
                NEW.nombre_normalizado := lower(trim(NEW.nombre));
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            CREATE TRIGGER trg_nombre_normalizado
            BEFORE INSERT OR UPDATE ON alimentos
            FOR EACH ROW EXECUTE FUNCTION set_nombre_normalizado();
        ");

        // 3 ── condiciones_medicas ──────────────────────────────────────────
        Schema::create('condiciones_medicas', function (Blueprint $t) {
            $t->id();
            $t->string('clave')->unique();
            $t->string('nombre')->unique();
            $t->text('descripcion')->nullable();
            $t->string('icono')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // 4 ── reglas_semaforo ──────────────────────────────────────────────
        Schema::create('reglas_semaforo', function (Blueprint $t) {
            $t->id();
            $t->foreignId('condicion_id')->constrained('condiciones_medicas');
            $t->string('campo_nutriente');
            $t->string('color')->comment('verde | amarillo | rojo');
            $t->decimal('umbral_min', 10, 2)->nullable();
            $t->decimal('umbral_max', 10, 2)->nullable();
            $t->text('descripcion')->nullable();
            $t->smallInteger('prioridad')->default(1);
            $t->timestamp('created_at')->useCurrent();
        });

        // 5 ── clasificaciones_cache ───────────────────────────────────────
        Schema::create('clasificaciones_cache', function (Blueprint $t) {
            $t->foreignId('alimento_id')->constrained('alimentos');
            $t->foreignId('condicion_id')->constrained('condiciones_medicas');
            $t->string('color')->comment('verde | amarillo | rojo');
            $t->text('razon')->nullable();
            $t->timestampTz('calculado_en')->useCurrent();
            $t->primary(['alimento_id', 'condicion_id']);
        });

        // 6 ── recetas ──────────────────────────────────────────────────────
        Schema::create('recetas', function (Blueprint $t) {
            $t->id();
            $t->string('nombre');
            $t->text('descripcion')->nullable();
            $t->text('instrucciones')->nullable();
            $t->smallInteger('porciones')->default(1);
            $t->smallInteger('tiempo_min')->nullable();
            $t->text('imagen_url')->nullable();
            $t->boolean('activa')->default(true);
            $t->timestampsTz();
        });

        // 7 ── receta_ingredientes ──────────────────────────────────────────
        Schema::create('receta_ingredientes', function (Blueprint $t) {
            $t->id();
            $t->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $t->foreignId('alimento_id')->constrained('alimentos');
            $t->decimal('cantidad', 10, 2);
            $t->string('unidad')->nullable();
            $t->text('notas')->nullable();
            $t->timestamp('created_at')->useCurrent();
        });

        // 8 ── receta_condiciones ───────────────────────────────────────────
        Schema::create('receta_condiciones', function (Blueprint $t) {
            $t->foreignId('receta_id')->constrained('recetas')->cascadeOnDelete();
            $t->foreignId('condicion_id')->constrained('condiciones_medicas');
            $t->string('color_promedio')->comment('verde | amarillo | rojo');
            $t->timestampTz('calculado_en')->useCurrent();
            $t->primary(['receta_id', 'condicion_id']);
        });

        // 9 ── profiles ─────────────────────────────────────────────────────
        // El id es el UUID de auth.users de Supabase
        Schema::create('profiles', function (Blueprint $t) {
            $t->uuid('id')->primary();
            $t->string('nombre')->nullable();
            $t->date('fecha_nacimiento')->nullable();
            $t->char('sexo', 1)->nullable()->comment('M | F | O');
            $t->decimal('peso_kg', 5, 2)->nullable();
            $t->decimal('talla_cm', 5, 1)->nullable();
            $t->timestampsTz();
        });

        // 10 ── usuario_condiciones ─────────────────────────────────────────
        Schema::create('usuario_condiciones', function (Blueprint $t) {
            $t->uuid('usuario_id');
            $t->foreignId('condicion_id')->constrained('condiciones_medicas');
            $t->boolean('activa')->default(true);
            $t->date('fecha_inicio')->useCurrent();
            $t->timestamp('created_at')->useCurrent();
            $t->primary(['usuario_id', 'condicion_id']);
        });

        // 11 ── favoritos_alimentos ─────────────────────────────────────────
        Schema::create('favoritos_alimentos', function (Blueprint $t) {
            $t->uuid('usuario_id');
            $t->foreignId('alimento_id')->constrained('alimentos');
            $t->timestamp('created_at')->useCurrent();
            $t->primary(['usuario_id', 'alimento_id']);
        });

        // 12 ── favoritos_recetas ───────────────────────────────────────────
        Schema::create('favoritos_recetas', function (Blueprint $t) {
            $t->uuid('usuario_id');
            $t->foreignId('receta_id')->constrained('recetas');
            $t->timestamp('created_at')->useCurrent();
            $t->primary(['usuario_id', 'receta_id']);
        });

        // 13 ── historial_busquedas ─────────────────────────────────────────
        Schema::create('historial_busquedas', function (Blueprint $t) {
            $t->id();
            $t->uuid('usuario_id');
            $t->string('termino');
            $t->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_busquedas');
        Schema::dropIfExists('favoritos_recetas');
        Schema::dropIfExists('favoritos_alimentos');
        Schema::dropIfExists('usuario_condiciones');
        Schema::dropIfExists('profiles');
        Schema::dropIfExists('receta_condiciones');
        Schema::dropIfExists('receta_ingredientes');
        Schema::dropIfExists('recetas');
        Schema::dropIfExists('clasificaciones_cache');
        Schema::dropIfExists('reglas_semaforo');
        Schema::dropIfExists('condiciones_medicas');
        Schema::dropIfExists('alimentos');
        Schema::dropIfExists('grupos_alimentos');

        \DB::unprepared('DROP TRIGGER IF EXISTS trg_nombre_normalizado ON alimentos');
        \DB::unprepared('DROP FUNCTION IF EXISTS set_nombre_normalizado()');
    }
};
