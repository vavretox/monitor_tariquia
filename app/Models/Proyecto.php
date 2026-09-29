<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Proyecto extends Model {
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['nombre','descripcion','tipo','estado','latitud','longitud','fecha_inicio','fecha_fin_estimada','presupuesto','user_id'];
    protected $casts = ['latitud'=>'float','longitud'=>'float','fecha_inicio'=>'date','fecha_fin_estimada'=>'date','presupuesto'=>'decimal:2'];

    public function getActivitylogOptions(): LogOptions {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
    public function archivos() { return $this->hasMany(ProyectoArchivo::class); }
    public function user() { return $this->belongsTo(User::class); }

    public function getTipoLabelAttribute(): string {
        return match ($this->tipo) {
            'salud' => 'Salud',
            'infraestructura_caminos' => 'Infraestructura de Caminos',
            default => $this->tipo,
        };
    }
    public function getEstadoLabelAttribute(): string {
        return match ($this->estado) {
            'planificado' => 'Planificado',
            'en_ejecucion' => 'En ejecución',
            'completado' => 'Completado',
            'suspendido' => 'Suspendido',
            default => $this->estado,
        };
    }
    public function getEstadoColorAttribute(): string {
        return match ($this->estado) {
            'planificado' => '#3b82f6',
            'en_ejecucion' => '#f59e0b',
            'completado' => '#10b981',
            'suspendido' => '#ef4444',
            default => '#6b7280',
        };
    }
}
