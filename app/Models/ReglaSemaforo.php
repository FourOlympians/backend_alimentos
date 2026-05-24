<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
