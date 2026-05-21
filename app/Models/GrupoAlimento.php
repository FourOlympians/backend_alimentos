<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrupoAlimento extends Model
{
    protected $table = 'grupos_alimentos';
    public $timestamps = false;

    protected $fillable = ['nombre', 'descripcion'];

    public function alimentos(): HasMany
    {
        return $this->hasMany(Alimento::class, 'grupo_id');
    }
}
