<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccionObservacion extends Model
{
    protected $table = 'accion_observaciones';
    protected $fillable = ['accion_ejecucion_id', 'user_id', 'parent_id', 'mensaje'];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
