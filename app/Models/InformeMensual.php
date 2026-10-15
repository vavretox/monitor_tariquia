<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InformeMensual extends Model
{
    use LogsActivity;

    protected $table = 'informes_mensuales';
    protected $guarded = [];
    protected $casts = [
        'presentado_at' => 'datetime', 'ultima_entrega_at' => 'datetime', 'revisado_at' => 'datetime',
        'recordatorio_at' => 'datetime', 'alerta_at' => 'datetime',
    ];

    public function accion(): BelongsTo
    {
        return $this->belongsTo(AccionEjecucion::class, 'accion_ejecucion_id');
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Responsable::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisor_id');
    }

    public function bitacora(): BelongsTo
    {
        return $this->belongsTo(AccionBitacora::class, 'accion_bitacora_id');
    }

    public function getEstadoControlAttribute(): string
    {
        if (! $this->accion_bitacora_id) {
            return $this->periodo < now(config('monthly_reports.timezone'))->format('Y-m') ? 'sin_informe' : 'pendiente';
        }

        return $this->estado;
    }

    public function getFueraDePlazoAttribute(): bool
    {
        return $this->presentado_at !== null
            && $this->presentado_at->copy()->setTimezone(config('monthly_reports.timezone'))->format('Y-m') > $this->periodo;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly([
            'accion_bitacora_id', 'estado', 'presentado_at', 'ultima_entrega_at', 'revisor_id', 'revisado_at', 'comentario_revision',
        ])->logOnlyDirty()->dontSubmitEmptyLogs();
    }
}
