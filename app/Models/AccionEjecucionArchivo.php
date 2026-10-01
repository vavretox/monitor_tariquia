<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccionEjecucionArchivo extends Model
{
    protected $fillable = [
        'accion_ejecucion_id',
        'ruta',
        'nombre_original',
        'tipo_mime',
        'tamano',
    ];

    public function accion(): BelongsTo
    {
        return $this->belongsTo(AccionEjecucion::class, 'accion_ejecucion_id');
    }
}
