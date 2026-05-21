<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alimento extends Model
{
    protected $table = 'alimentos';

    protected $fillable = [
        'grupo_id', 'nombre', 'cantidad_sugerida', 'unidad',
        'peso_bruto_g', 'peso_neto_g', 'energia_kcal',
        'proteina_g', 'lipidos_g', 'hidratos_carbono_g',
        'ag_saturados_g', 'ag_monoinsaturados_g', 'ag_poliinsaturados_g',
        'colesterol_mg', 'azucar_g', 'fibra_g',
        'vitamina_a_mg_re', 'acido_ascorbico_mg', 'acido_folico_mg',
        'calcio_mg', 'hierro_mg', 'potasio_mg', 'sodio_mg', 'fosforo_mg',
        'etanol_g', 'indice_glucemico', 'carga_glucemica', 'contiene_gluten',
    ];

    protected $casts = [
        'contiene_gluten' => 'boolean',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────────

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(GrupoAlimento::class, 'grupo_id');
    }

    public function ingredientes(): HasMany
    {
        return $this->hasMany(RecetaIngrediente::class, 'alimento_id');
    }

    public function clasificaciones(): HasMany
    {
        return $this->hasMany(ClasificacionCache::class, 'alimento_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────────

    public function scopeSearch($query, ?string $term)
    {
        if ($term) {
            $query->where('nombre_normalizado', 'ilike', '%' . strtolower($term) . '%');
        }
        return $query;
    }

    public function scopeDelGrupo($query, ?int $grupoId)
    {
        if ($grupoId) {
            $query->where('grupo_id', $grupoId);
        }
        return $query;
    }
}
