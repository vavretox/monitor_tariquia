<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AccionEjecucion extends Model
{
    use LogsActivity;

    protected static function booted(): void
    {
        static::addGlobalScope('responsable', function ($query) {
            $user = auth()->user();
            if ($user?->hasRole('responsable')) {
                $id = $user->responsable?->id ?? 0;
                $query->whereHas('responsables', fn ($responsables) => $responsables->where('responsables.id', $id));
            }
        });
        static::created(function ($model) {
            $user = auth()->user();
            $id = $user?->hasRole('responsable') ? $user->responsable?->id : null;
            if ($id) {
                $model->responsables()->syncWithoutDetaching([$id]);
                $model->forceFill(['responsable_id' => $id])->saveQuietly();
            }
        });
    }

    protected $table = 'acciones_ejecucion';

    protected $fillable = [
        'demanda_id', 'responsable_id', 'titulo', 'descripcion', 'estado',
        'avance', 'ultima_actualizacion_avance', 'fecha_inicio', 'fecha_limite', 'resultado_esperado', 'proximo_paso', 'resultado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_limite' => 'date',
        'avance' => 'integer',
        'ultima_actualizacion_avance' => 'datetime',
    ];

    public function demanda(): BelongsTo
    {
        return $this->belongsTo(Demanda::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Responsable::class);
    }

    public function responsables(): BelongsToMany
    {
        return $this->belongsToMany(Responsable::class, 'accion_ejecucion_responsable')->withTimestamps();
    }

    public function bitacoras(): HasMany
    {
        return $this->hasMany(AccionBitacora::class, 'accion_ejecucion_id')->latest('fecha_hora');
    }

    public function getDiasRetrasoAttribute(): int
    {
        return $this->fecha_limite && $this->estado !== 'completada' && $this->fecha_limite->isBefore(today())
            ? $this->fecha_limite->diffInDays(today()) : 0;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
