<?php
namespace App\Http\Controllers;
use App\Models\Proyecto;
use App\Models\ProyectoArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProyectoArchivoController extends Controller {
    public function store(Request $request, Proyecto $proyecto) {
        $request->validate(['archivo' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,docx,xlsx']);
        $file = $request->file('archivo');
        $ruta = $file->store("proyectos/{$proyecto->id}", 'local');
        $proyecto->archivos()->create([
            'ruta' => $ruta,
            'nombre_original' => $file->getClientOriginalName(),
            'tipo_mime' => $file->getClientMimeType(),
            'tamano' => $file->getSize(),
        ]);
        return back()->with('success','Archivo subido.');
    }
    public function destroy(ProyectoArchivo $archivo) {
        Storage::disk('local')->delete($archivo->ruta);
        $archivo->delete();
        return back()->with('success','Archivo eliminado.');
    }
}
