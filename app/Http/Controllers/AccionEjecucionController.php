<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Support\MonitoringCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AccionEjecucionController extends Controller
{
    public function index(Request $request): View
    {
        $acciones = AccionEjecucion::with(['demanda.comunidades', 'responsables'])->withCount('archivos')
            ->when($request->filled('buscar'), fn ($query) => $query->where('titulo', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->latest()->paginate(15)->withQueryString();

        $estadosAccion = MonitoringCatalog::ACTION_STATES;

        return view('acciones_ejecucion.index', compact('acciones', 'estadosAccion'));
    }

    public function create(Request $request): View
    {
        return view('acciones_ejecucion.create', $this->catalogos($request->integer('demanda_id') ?: null));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);
        $responsables = $data['responsable_ids'] ?? [];
        $documentos = $request->file('documentos', []);
        $comentario = $data['comentario_avance'] ?? null;
        unset($data['responsable_ids'], $data['documentos'], $data['eliminar_documentos'], $data['comentario_avance']);
        $data['responsable_id'] = $responsables[0] ?? null;
        $storedPaths = [];

        try {
            $accion = DB::transaction(function () use ($data, $responsables, $documentos, $comentario, &$storedPaths) {
                $data['ultima_actualizacion_avance'] = now();
                $accion = AccionEjecucion::create($data);
                $accion->responsables()->sync($responsables);
                $accion->historialAvances()->create(['user_id' => auth()->id(), 'avance' => $accion->avance, 'estado' => $accion->estado, 'comentario' => $comentario ?: 'Registro inicial de la acción.']);
                $this->guardarDocumentos($accion, $documentos, $storedPaths);

                return $accion;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        return redirect()->route('demandas.show', $accion->demanda_id)->with('success', 'Acción registrada correctamente.');
    }

    public function edit(AccionEjecucion $accione): View
    {
        $accione->load(['responsables', 'archivos']);

        return view('acciones_ejecucion.edit', ['accionEjecucion' => $accione] + $this->catalogos());
    }

    public function update(Request $request, AccionEjecucion $accione): RedirectResponse
    {
        $data = $this->validar($request);
        $responsables = $data['responsable_ids'] ?? [];
        $documentos = $request->file('documentos', []);
        $eliminarIds = $data['eliminar_documentos'] ?? [];
        $comentario = $data['comentario_avance'] ?? null;
        unset($data['responsable_ids'], $data['documentos'], $data['eliminar_documentos'], $data['comentario_avance']);
        $data['responsable_id'] = $responsables[0] ?? null;
        $storedPaths = [];
        $deleteAfterCommit = [];

        try {
            DB::transaction(function () use ($accione, $data, $responsables, $documentos, $eliminarIds, $comentario, &$storedPaths, &$deleteAfterCommit) {
                $cambio = $accione->estado !== $data['estado'] || (array_key_exists('avance', $data) && (int) $accione->avance !== (int) $data['avance']);
                if ($cambio) {
                    $data['ultima_actualizacion_avance'] = now();
                }
                $accione->update($data);
                $accione->responsables()->sync($responsables);
                if ($cambio) {
                    $accione->historialAvances()->create(['user_id' => auth()->id(), 'avance' => $accione->avance, 'estado' => $accione->estado, 'comentario' => $comentario]);
                }
                $archivos = $accione->archivos()->whereIn('id', $eliminarIds)->get();
                $deleteAfterCommit = $archivos->pluck('ruta')->all();
                $accione->archivos()->whereIn('id', $eliminarIds)->delete();
                $this->guardarDocumentos($accione, $documentos, $storedPaths);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);
            throw $exception;
        }

        Storage::disk('local')->delete($deleteAfterCommit);

        return redirect()->route('demandas.show', $accione->demanda_id)->with('success', 'Acción actualizada correctamente.');
    }

    public function destroy(AccionEjecucion $accione): RedirectResponse
    {
        $paths = $accione->archivos()->pluck('ruta')->all();
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
            'demanda_id' => ['required', 'exists:demandas,id'], 'responsable_ids' => ['nullable', 'array'],
            'responsable_ids.*' => ['integer', 'distinct', 'exists:responsables,id'], 'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'], 'resultado_esperado' => ['nullable', 'string'],
            'estado' => ['required', Rule::in(MonitoringCatalog::ACTION_STATES)], 'avance' => ['sometimes', 'integer', 'between:0,100'],
            'comentario_avance' => ['nullable', 'string', 'max:2000'], 'fecha_inicio' => ['nullable', 'date'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:fecha_inicio'], 'proximo_paso' => ['nullable', 'string'],
            'resultado' => ['nullable', 'string'], 'evidencias' => ['nullable', 'string'], 'documentos' => ['nullable', 'array', 'max:10'],
            'documentos.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'], 'eliminar_documentos' => ['nullable', 'array'],
            'eliminar_documentos.*' => ['integer', 'distinct'],
        ]);
    }

    private function guardarDocumentos(AccionEjecucion $accion, array $documentos, array &$storedPaths): void
    {
        foreach ($documentos as $documento) {
            $ruta = $documento->store("acciones/{$accion->id}", 'local');
            $storedPaths[] = $ruta;
            $accion->archivos()->create(['ruta' => $ruta, 'nombre_original' => $documento->getClientOriginalName(), 'tipo_mime' => $documento->getClientMimeType(), 'tamano' => $documento->getSize()]);
        }
    }
}
