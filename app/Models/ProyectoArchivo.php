<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ProyectoArchivo extends Model {
    protected $fillable = ['proyecto_id','ruta','nombre_original','tipo_mime','tamano'];
    public function proyecto() { return $this->belongsTo(Proyecto::class); }
}
