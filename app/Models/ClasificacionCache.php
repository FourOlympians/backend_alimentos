<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
