<?php

namespace App\Http\Controllers;

use App\Models\AccionCompromiso;
use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\Responsable;
use App\Http\Controllers\Concerns\RestrictsResponsableAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccionCompromisoController extends Controller
{
    public function index(Request $request)
    {
        $acciones = AccionCompromiso::with(['necesidad.comunidades', 'accionesEjecucion'])
            ->when($request->buscar, fn ($query, $value) => $query->where(fn ($query) => $query->where('titulo', 'like', "%$value%")->orWhere('area', 'like', "%$value%")))
            ->when($request->comunidad_id, fn ($query, $value) => $query->whereHas('necesidad.comunidades', fn ($query) => $query->where('comunidades.id', $value)))
            ->when($request->estado, fn ($query, $value) => $query->where('estado', $value))
            ->latest()->paginate(15)->withQueryString();
        $comunidades = Comunidad::orderBy('nombre')->get();
        return view('acciones.index', compact('acciones', 'comunidades'));
    }

    public function create(Request $request) { return view('acciones.create', $this->catalogos() + ['necesidadSeleccionada' => $request->necesidad_id]); }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $data['comunidad_id'] = Necesidad::findOrFail($data['necesidad_id'])->comunidades()->value('comunidades.id');
        AccionCompromiso::create($data);
        return redirect()->route('compromisos.index')->with('success', 'Compromiso registrado.');
    }

    public function show(AccionCompromiso $accione)
    {
        $accione->load(['necesidad.comunidades', 'accionesEjecucion.responsables', 'accionesEjecucion.archivos', 'accionesEjecucion.historialAvances.usuario']);
        return view('acciones.show', ['accion' => $accione]);
    }

    public function edit(AccionCompromiso $accione) { return view('acciones.edit', ['accion' => $accione] + $this->catalogos()); }

    public function update(Request $request, AccionCompromiso $accione)
    {
        $data = $this->validar($request);
        $data['comunidad_id'] = Necesidad::findOrFail($data['necesidad_id'])->comunidades()->value('comunidades.id');
        $accione->update($data);
        return redirect()->route('compromisos.index')->with('success', 'Compromiso actualizado.');
    }

    public function destroy(AccionCompromiso $accione)
    {
        foreach ($accione->documentos ?? [] as $documento) Storage::disk('public')->delete($documento);
        $accione->delete();
        return back()->with('success', 'Compromiso eliminado.');
    }

    private function catalogos(): array
    {
        return [
            'comunidades' => Comunidad::orderBy('nombre')->get(),
            'necesidades' => Necesidad::with('comunidades')->orderBy('titulo')->get(),
            'areas' => ['Agua', 'Caminos y Accesibilidad', 'Educación', 'Energía', 'Gestión Institucional', 'Medio Ambiente', 'Producción y Comercialización', 'Salud', 'Telecomunicaciones', 'Turismo'],
        ];
    }

    private function validar(Request $request): array
    {
        $data = $request->validate([
            'necesidad_id' => ['required', 'exists:necesidades,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'area' => ['required', 'in:Agua,Caminos y Accesibilidad,Educación,Energía,Gestión Institucional,Medio Ambiente,Producción y Comercialización,Salud,Telecomunicaciones,Turismo'],
            'prioridad' => ['nullable', 'in:baja,media,alta,critica'],
            'fecha_compromiso' => ['nullable', 'date'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:fecha_compromiso'],
            'estado' => ['required', 'in:identificado,planificado,en_gestion,en_ejecucion,cumplido,suspendido'],
            'proximo_paso' => ['nullable', 'string'],
            'fecha_proximo_paso' => ['nullable', 'date'],
            'problema_bloqueo' => ['nullable', 'string'],
            'requiere_decision' => ['nullable', 'boolean'],
            'decision_requerida' => ['nullable', 'string'],
            'resultado_esperado' => ['nullable', 'string'],
        ]);
        return $data;
    }
}
