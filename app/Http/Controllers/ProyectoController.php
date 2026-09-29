<?php
namespace App\Http\Controllers;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProyectoController extends Controller {
    public function index() {
        $proyectos = Proyecto::latest()->paginate(10);
        return view('proyectos.index', compact('proyectos'));
    }
    public function create() { return view('proyectos.create'); }
    public function store(Request $request) {
        $validated = $this->validar($request);
        $validated['user_id'] = auth()->id();
        Proyecto::create($validated);
        return redirect()->route('dashboard')->with('success','Proyecto creado correctamente.');
    }
    public function show(Proyecto $proyecto) {
        $proyecto->load('archivos','user');
        return view('proyectos.show', compact('proyecto'));
    }
    public function edit(Proyecto $proyecto) { return view('proyectos.edit', compact('proyecto')); }
    public function update(Request $request, Proyecto $proyecto) {
        $proyecto->update($this->validar($request));
        return redirect()->route('dashboard')->with('success','Proyecto actualizado.');
    }
    public function destroy(Proyecto $proyecto) {
        $proyecto->delete();
        return redirect()->route('dashboard')->with('success','Proyecto archivado.');
    }
    public function forceDelete(Proyecto $proyecto) {
        foreach ($proyecto->archivos as $a) Storage::disk('local')->delete($a->ruta);
        $proyecto->forceDelete();
        return redirect()->route('dashboard')->with('success','Proyecto eliminado definitivamente.');
    }
    private function validar(Request $request): array {
        return $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo' => 'required|in:salud,infraestructura_caminos',
            'estado' => 'required|in:planificado,en_ejecucion,completado,suspendido',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'descripcion' => 'nullable|string|max:2000',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin_estimada' => 'nullable|date|after_or_equal:fecha_inicio',
            'presupuesto' => 'nullable|numeric|min:0',
        ]);
    }
}
