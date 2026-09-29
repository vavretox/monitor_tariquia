<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model; use Spatie\Activitylog\LogOptions; use Spatie\Activitylog\Traits\LogsActivity;
class Comunidad extends Model {use LogsActivity;protected $table='comunidades';protected $fillable=['nombre','territorio','latitud','longitud'];protected $casts=['latitud'=>'float','longitud'=>'float'];public function necesidadesRelacionadas(){return $this->belongsToMany(Necesidad::class,'comunidad_necesidad')->withTimestamps();}public function accionesCompromisos(){return $this->hasMany(AccionCompromiso::class);}public function getActivitylogOptions():LogOptions{return LogOptions::defaults()->logAll()->logOnlyDirty();}}
