<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Demanda extends Model
{
    use LogsActivity;

    protected $fillable = [
        'tipo_necesidad_id', 'titulo', 'descripcion', 'ubicacion_especifica', 'fuente',
        'fecha_identificacion', 'fecha_limite', 'prioridad', 'estado', 'resultado_esperado',
        'proximo_paso', 'problema_bloqueo',
    ];

    protected $casts = ['fecha_identificacion' => 'date', 'fecha_limite' => 'date'];

    public function comunidades(): BelongsToMany
    {
        return $this->belongsToMany(Comunidad::class, 'comunidad_demanda')->withTimestamps();
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoNecesidad::class, 'tipo_necesidad_id');
    }

    public function acciones(): HasMany
    {
        return $this->hasMany(AccionEjecucion::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
