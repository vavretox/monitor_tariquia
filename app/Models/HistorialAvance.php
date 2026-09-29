<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialAvance extends Model
{
    protected $table = 'historial_avances';
    protected $guarded = [];

    public function accion()
    {
        return $this->belongsTo(AccionEjecucion::class, 'accion_ejecucion_id');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
