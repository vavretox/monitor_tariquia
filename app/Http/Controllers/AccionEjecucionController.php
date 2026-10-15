<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\TipoNecesidad;
use App\Support\MonitoringCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AccionEjecucionController extends Controller
{
    public function index(Request $request): View
    {
        $acciones = AccionEjecucion::with(['demanda.comunidades', 'comunidades', 'responsables'])->withCount('bitacoras')
            ->when($request->filled('buscar'), fn ($query) => $query->where('titulo', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('comunidad_id'), fn ($query) => $query->whereHas('comunidades', fn ($communityQuery) => $communityQuery->where('comunidades.id', $request->input('comunidad_id'))))
            ->when($request->filled('tipo_necesidad_id'), fn ($query) => $query->whereHas('demanda', fn ($demandQuery) => $demandQuery->where('tipo_necesidad_id', $request->input('tipo_necesidad_id'))))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->latest()->paginate(15)->withQueryString();
        $estadosAccion = MonitoringCatalog::ACTION_STATES;

        $comunidades = Comunidad::orderBy('nombre')->get();
        $tiposNecesidad = TipoNecesidad::orderBy('nombre')->get();

        return view('acciones_ejecucion.index', compact('acciones', 'estadosAccion', 'comunidades', 'tiposNecesidad'));
    }

    public function show(AccionEjecucion $accione): View
    {
        $accione->load(['demanda.comunidades', 'comunidades', 'responsables', 'bitacoras.usuario', 'bitacoras.fotos']);

        return view('acciones_ejecucion.show', ['accion' => $accione]);
    }

    public function create(Request $request): View
    {
        return view('acciones_ejecucion.create', $this->catalogos($request->integer('demanda_id') ?: null));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);
        $responsables = $data['responsable_ids'] ?? [];
        $comunidades = $data['comunidad_ids'];
        unset($data['responsable_ids'], $data['comunidad_ids']);
        $data['responsable_id'] = $responsables[0] ?? null;
        $data['ultima_actualizacion_avance'] = now();

        $accion = DB::transaction(function () use ($data, $responsables, $comunidades) {
            $accion = AccionEjecucion::create($data);
            $accion->responsables()->sync($responsables);
            $accion->comunidades()->sync($comunidades);
            app(\App\Support\MonthlyReportControl::class)->synchronize();

            return $accion;
        });

        return redirect()->route('demandas.show', $accion->demanda_id)->with('success', 'Acción registrada correctamente.');
    }

    public function edit(AccionEjecucion $accione): View
    {
        $accione->load(['responsables', 'comunidades']);

        return view('acciones_ejecucion.edit', ['accionEjecucion' => $accione] + $this->catalogos());
    }

    public function update(Request $request, AccionEjecucion $accione): RedirectResponse
    {
        $data = $this->validar($request);
        $responsables = $data['responsable_ids'] ?? [];
        $comunidades = $data['comunidad_ids'];
        unset($data['responsable_ids'], $data['comunidad_ids']);
        $data['responsable_id'] = $responsables[0] ?? null;

        DB::transaction(function () use ($accione, $data, $responsables, $comunidades) {
            if ($accione->estado !== $data['estado'] || (int) $accione->avance !== (int) ($data['avance'] ?? 0)) {
                $data['ultima_actualizacion_avance'] = now();
            }
            app(\App\Support\MonthlyReportControl::class)->synchronize();
            $accione->update($data);
            $antes = [
                'responsables' => $accione->responsables()->pluck('nombre_completo')->join(', '),
                'comunidades' => $accione->comunidades()->pluck('nombre')->join(', '),
            ];
            $accione->responsables()->sync($responsables);
            $accione->comunidades()->sync($comunidades);
            app(\App\Support\MonthlyReportControl::class)->synchronize();
            $despues = [
                'responsables' => $accione->responsables()->pluck('nombre_completo')->join(', '),
                'comunidades' => $accione->comunidades()->pluck('nombre')->join(', '),
            ];
            if ($antes !== $despues) {
                activity()->performedOn($accione)->causedBy(auth()->user())->event('updated')
                    ->withProperties(['old' => $antes, 'attributes' => $despues])
                    ->log('Asignación actualizada');
            }
        });

        return redirect()->route('acciones.show', $accione)->with('success', 'Acción actualizada correctamente.');
    }

    public function destroy(AccionEjecucion $accione): RedirectResponse
    {
        $paths = $accione->bitacoras()->with('fotos')->get()->flatMap->fotos->pluck('ruta')->all();
        DB::transaction(fn () => $accione->delete());
        Storage::disk('local')->delete($paths);

        return back()->with('success', 'Acción eliminada.');
    }

    private function catalogos(?int $demandaId = null): array
    {
        return ['demandas' => Demanda::with('comunidades')->orderBy('titulo')->get(), 'responsables' => Responsable::orderBy('nombre_completo')->get(), 'demandaId' => $demandaId, 'estadosAccion' => MonitoringCatalog::ACTION_STATES];
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'demanda_id' => ['required', 'exists:demandas,id'],
            'comunidad_ids' => ['required', 'array', 'min:1'],
            'comunidad_ids.*' => [
                'integer', 'distinct',
                Rule::exists('comunidad_demanda', 'comunidad_id')->where(
                    fn ($query) => $query->where('demanda_id', $request->input('demanda_id'))
                ),
            ],
            'responsable_ids' => ['nullable', 'array'],
            'responsable_ids.*' => ['integer', 'distinct', 'exists:responsables,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'resultado_esperado' => ['nullable', 'string'],
            'estado' => ['required', Rule::in(MonitoringCatalog::ACTION_STATES)],
            'avance' => ['sometimes', 'integer', 'between:0,100'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'proximo_paso' => ['nullable', 'string'],
            'resultado' => ['nullable', 'string'],
        ]);
    }
}
