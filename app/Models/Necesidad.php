<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Necesidad extends Model
{
    use LogsActivity;
    protected $table = 'necesidades';

    protected $fillable = ['comunidad_id', 'tipo_necesidad_id', 'titulo', 'descripcion', 'ubicacion_especifica', 'fuente', 'fecha_identificacion', 'prioridad', 'estado'];

    protected $casts = ['fecha_identificacion' => 'date'];

    public function comunidad(): BelongsTo
    {
        return $this->belongsTo(Comunidad::class);
    }

    public function comunidades(): BelongsToMany
    {
        return $this->belongsToMany(Comunidad::class, 'comunidad_necesidad')->withTimestamps();
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TipoNecesidad::class, 'tipo_necesidad_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }

    public function compromisos(): HasMany
    {
        return $this->hasMany(AccionCompromiso::class);
    }
}