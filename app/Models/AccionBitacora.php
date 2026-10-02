<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccionBitacora extends Model
{
    protected $fillable = ['accion_ejecucion_id', 'user_id', 'fecha_hora', 'descripcion', 'avance'];

    protected $casts = ['fecha_hora' => 'datetime', 'avance' => 'integer'];

    public function accion(): BelongsTo
    {
        return $this->belongsTo(AccionEjecucion::class, 'accion_ejecucion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(AccionBitacoraFoto::class, 'accion_bitacora_id');
    }
}
