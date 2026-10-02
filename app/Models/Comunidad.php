<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Comunidad extends Model
{
    use LogsActivity;

    protected $table = 'comunidades';

    protected $fillable = ['nombre', 'territorio', 'latitud', 'longitud'];

    protected $casts = ['latitud' => 'float', 'longitud' => 'float'];

    public function demandas()
    {
        return $this->belongsToMany(Demanda::class, 'comunidad_demanda')->withTimestamps();
    }

    public function acciones()
    {
        return $this->belongsToMany(AccionEjecucion::class, 'accion_ejecucion_comunidad')->withTimestamps();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->logOnlyDirty();
    }
}
