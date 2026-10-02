<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccionBitacoraFoto extends Model
{
    protected $fillable = ['accion_bitacora_id', 'ruta', 'nombre_original', 'tipo_mime', 'tamano'];

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(AccionBitacora::class, 'accion_bitacora_id');
    }
}
