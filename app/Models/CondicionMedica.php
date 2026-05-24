<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
