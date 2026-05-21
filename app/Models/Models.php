<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// ──────────────────────────────────────────────────────────────────────────────
// Receta
// ──────────────────────────────────────────────────────────────────────────────
class Receta extends Model
{
    protected $table = 'recetas';

    protected $fillable = [
        'nombre', 'descripcion', 'instrucciones',
        'porciones', 'tiempo_min', 'imagen_url', 'activa',
    ];

    protected $casts = ['activa' => 'boolean'];

    public function ingredientes(): HasMany
    {
        return $this->hasMany(RecetaIngrediente::class, 'receta_id');
    }

    public function condiciones(): BelongsToMany
    {
        return $this->belongsToMany(
            CondicionMedica::class,
            'receta_condiciones',
            'receta_id',
            'condicion_id'
        )->withPivot('color_promedio', 'calculado_en');
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// RecetaIngrediente
// ──────────────────────────────────────────────────────────────────────────────
class RecetaIngrediente extends Model
{
    protected $table = 'receta_ingredientes';
    public $timestamps = false;

    protected $fillable = ['receta_id', 'alimento_id', 'cantidad', 'unidad', 'notas'];

    public function alimento()
    {
        return $this->belongsTo(Alimento::class, 'alimento_id');
    }

    public function receta()
    {
        return $this->belongsTo(Receta::class, 'receta_id');
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// CondicionMedica
// ──────────────────────────────────────────────────────────────────────────────
class CondicionMedica extends Model
{
    protected $table = 'condiciones_medicas';
    public $timestamps = false;

    protected $fillable = ['clave', 'nombre', 'descripcion', 'icono'];

    public function reglas()
    {
        return $this->hasMany(ReglaSemaforo::class, 'condicion_id');
    }

    public function clasificaciones()
    {
        return $this->hasMany(ClasificacionCache::class, 'condicion_id');
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// ClasificacionCache
// ──────────────────────────────────────────────────────────────────────────────
class ClasificacionCache extends Model
{
    protected $table = 'clasificaciones_cache';
    public $timestamps = false;

    protected $primaryKey = null;
    public $incrementing  = false;

    protected $fillable = ['alimento_id', 'condicion_id', 'color', 'razon', 'calculado_en'];

    public function alimento()
    {
        return $this->belongsTo(Alimento::class, 'alimento_id');
    }

    public function condicion()
    {
        return $this->belongsTo(CondicionMedica::class, 'condicion_id');
    }
}

// ──────────────────────────────────────────────────────────────────────────────
// ReglaSemaforo
// ──────────────────────────────────────────────────────────────────────────────
class ReglaSemaforo extends Model
{
    protected $table = 'reglas_semaforo';
    public $timestamps = false;

    protected $fillable = [
        'condicion_id', 'campo_nutriente', 'color',
        'umbral_min', 'umbral_max', 'descripcion', 'prioridad',
    ];

    public function condicion()
    {
        return $this->belongsTo(CondicionMedica::class, 'condicion_id');
    }
}
