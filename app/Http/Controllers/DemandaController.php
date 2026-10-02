<?php

namespace App\Http\Controllers;

use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\TipoNecesidad;
use App\Support\MonitoringCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DemandaController extends Controller
{
    public function index(Request $request)
    {
        $demandas = Demanda::with(['comunidades', 'tipo'])->withCount('acciones')
            ->when($request->buscar, fn ($q, $v) => $q->where(fn ($q) => $q->where('titulo', 'like', "%$v%")->orWhere('descripcion', 'like', "%$v%")))
            ->when($request->comunidad_id, fn ($q, $v) => $q->whereHas('comunidades', fn ($q) => $q->where('comunidades.id', $v)))
            ->when($request->tipo_necesidad_id, fn ($q, $v) => $q->where('tipo_necesidad_id', $v))
            ->when($request->estado, fn ($q, $v) => $q->where('estado', $v))
            ->latest()->paginate(15)->withQueryString();

        return view('demandas.index', $this->catalogos() + compact('demandas'));
    }

    public function show(Demanda $demanda)
    {
        $demanda->load(['tipo', 'comunidades', 'acciones.responsables']);

        return view('demandas.show', compact('demanda'));
    }

    public function create(Request $request)
    {
        return view('demandas.create', $this->catalogos() + ['comunidadId' => $request->comunidad_id]);
    }

    public function store(Request $request)
    {
        [$data, $communityIds] = $this->validated($request);
        $demanda = DB::transaction(function () use ($data, $communityIds) {
            $demanda = Demanda::create($data);
            $demanda->comunidades()->sync($communityIds);

            return $demanda;
        });

        return redirect()->route('demandas.show', $demanda)->with('success', 'Demanda registrada. Ya puede generar sus acciones.');
    }

    public function edit(Demanda $demanda)
    {
        $demanda->load('comunidades');

        return view('demandas.edit', $this->catalogos() + compact('demanda'));
    }

    public function update(Request $request, Demanda $demanda)
    {
        [$data, $communityIds] = $this->validated($request);
        DB::transaction(function () use ($demanda, $data, $communityIds) {
            $demanda->update($data);
            $demanda->comunidades()->sync($communityIds);
        });

        return redirect()->route('demandas.show', $demanda)->with('success', 'Demanda actualizada.');
    }

    public function destroy(Demanda $demanda)
    {
        $paths = $demanda->acciones()->with('bitacoras.fotos')->get()
            ->flatMap->bitacoras->flatMap->fotos->pluck('ruta')->all();
        DB::transaction(fn () => $demanda->delete());
        Storage::disk('local')->delete($paths);

        return redirect()->route('demandas.index')->with('success', 'Demanda eliminada.');
    }

    private function catalogos(): array
    {
        return ['comunidades' => Comunidad::orderBy('nombre')->get(), 'tiposNecesidad' => TipoNecesidad::orderBy('nombre')->get(), 'estadosDemanda' => MonitoringCatalog::DEMAND_STATES, 'prioridadesDemanda' => MonitoringCatalog::DEMAND_PRIORITIES];
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'comunidad_ids' => ['required', 'array', 'min:1'],
            'comunidad_ids.*' => ['integer', 'distinct', 'exists:comunidades,id'],
            'tipo_necesidad_id' => ['nullable', 'integer', 'exists:tipos_necesidad,id'],
            'titulo' => ['required', 'string', 'max:255'], 'descripcion' => ['nullable', 'string'],
            'ubicacion_especifica' => ['nullable', 'string', 'max:255'], 'fuente' => ['nullable', 'string', 'max:255'],
            'fecha_identificacion' => ['nullable', 'date'], 'fecha_limite' => ['nullable', 'date', 'after_or_equal:fecha_identificacion'],
            'prioridad' => ['required', Rule::in(MonitoringCatalog::DEMAND_PRIORITIES)],
            'estado' => ['required', Rule::in(MonitoringCatalog::DEMAND_STATES)],
            'resultado_esperado' => ['nullable', 'string'], 'proximo_paso' => ['nullable', 'string'], 'problema_bloqueo' => ['nullable', 'string'],
        ]);
        $communityIds = $data['comunidad_ids'];
        unset($data['comunidad_ids']);

        return [$data, $communityIds];
    }
}
