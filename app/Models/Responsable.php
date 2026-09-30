<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Spatie\Activitylog\LogOptions;use Spatie\Activitylog\Traits\LogsActivity;
class Responsable extends Model{use LogsActivity;protected $fillable=['user_id','nombre_completo','cargo_rol','area','telefono','email','notas','actualizaciones_registradas','institucion'];public function user(){return $this->belongsTo(User::class);}public function areas(){return $this->belongsToMany(Area::class,'area_responsable')->withTimestamps();}public function accionesEjecucion(){return $this->belongsToMany(AccionEjecucion::class,'accion_ejecucion_responsable')->withTimestamps();}public function acciones(){return $this->accionesEjecucion();}public function getActivitylogOptions():LogOptions{return LogOptions::defaults()->logAll()->logOnlyDirty();}}

