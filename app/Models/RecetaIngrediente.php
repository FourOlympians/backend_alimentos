<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
