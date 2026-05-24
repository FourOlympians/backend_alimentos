<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
