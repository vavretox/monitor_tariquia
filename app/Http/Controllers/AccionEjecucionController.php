<?php

namespace App\Http\Controllers;

use App\Models\Demanda;
use App\Models\AccionEjecucion;
use App\Models\Responsable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AccionEjecucionController extends Controller
{
    public function index(Request $request): View
    {
        $acciones = AccionEjecucion::with(['demanda.comunidades', 'responsables'])
            ->withCount('archivos')
            ->when($request->filled('buscar'), fn ($query) => $query->where('titulo', 'like', '%'.$request->buscar.'%'))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('acciones_ejecucion.index', compact('acciones'));
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
        $comentarioAvance = $data['comentario_avance'] ?? null;
        unset($data['responsable_ids'], $data['documentos'], $data['eliminar_documentos'], $data['comentario_avance']);
        $data['responsable_id'] = $responsables[0] ?? null;

        $accion = DB::transaction(function () use ($data, $responsables, $comentarioAvance) {
            $data['ultima_actualizacion_avance'] = now();
            $accion = AccionEjecucion::create($data);
            $accion->responsables()->sync($responsables);
            $accion->historialAvances()->create(['user_id' => auth()->id(), 'avance' => $accion->avance, 'estado' => $accion->estado, 'comentario' => $comentarioAvance ?: 'Registro inicial de la acción.']);

            return $accion;
        });

        $this->guardarDocumentos($accion, $documentos);

        return redirect()->route('demandas.show', $accion->demanda_id)
            ->with('success', 'Acción registrada correctamente.');
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
        $eliminarDocumentos = $data['eliminar_documentos'] ?? [];
        $comentarioAvance = $data['comentario_avance'] ?? null;
        unset($data['responsable_ids'], $data['documentos'], $data['eliminar_documentos'], $data['comentario_avance']);
        $data['responsable_id'] = $responsables[0] ?? null;

        DB::transaction(function () use ($accione, $data, $responsables, $comentarioAvance) {
            $cambioSeguimiento = $accione->estado !== $data['estado']
                || (array_key_exists('avance', $data) && (int) $accione->avance !== (int) $data['avance']);
            if ($cambioSeguimiento) $data['ultima_actualizacion_avance'] = now();
            $accione->update($data);
            $accione->responsables()->sync($responsables);
            if ($cambioSeguimiento) {
                $accione->historialAvances()->create(['user_id' => auth()->id(), 'avance' => $accione->avance, 'estado' => $accione->estado, 'comentario' => $comentarioAvance]);
            }
        });

        if ($eliminarDocumentos) {
            $archivos = $accione->archivos()->whereIn('id', $eliminarDocumentos)->get();
            foreach ($archivos as $archivo) {
                Storage::disk('local')->delete($archivo->ruta);
                $archivo->delete();
            }
        }

        $this->guardarDocumentos($accione, $documentos);

        return redirect()->route('demandas.show', $accione->demanda_id)
            ->with('success', 'Acción actualizada correctamente.');
    }

    public function destroy(AccionEjecucion $accione): RedirectResponse
    {
        $accione->load('archivos');
        foreach ($accione->archivos as $archivo) {
            Storage::disk('local')->delete($archivo->ruta);
        }
        $accione->delete();

        return back()->with('success', 'Acción eliminada.');
    }

    private function catalogos(?int $demandaId = null): array
    {
        return [
            'demandas' => Demanda::with('comunidades')->orderBy('titulo')->get(),
            'responsables' => Responsable::orderBy('nombre_completo')->get(),
            'demandaId' => $demandaId,
        ];
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'demanda_id' => ['required', 'exists:demandas,id'],
            'responsable_ids' => ['nullable', 'array'],
            'responsable_ids.*' => ['integer', 'distinct', 'exists:responsables,id'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'resultado_esperado' => ['nullable', 'string'],
            'estado' => ['required', 'in:pendiente,en_ejecucion,completada,bloqueada'],
            'avance' => ['sometimes', 'integer', 'between:0,100'],
            'comentario_avance' => ['nullable', 'string', 'max:2000'],
            'fecha_inicio' => ['nullable', 'date'],
            'fecha_limite' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'proximo_paso' => ['nullable', 'string'],
            'resultado' => ['nullable', 'string'],
            'evidencias' => ['nullable', 'string'],
            'documentos' => ['nullable', 'array', 'max:10'],
            'documentos.*' => ['file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
            'eliminar_documentos' => ['nullable', 'array'],
            'eliminar_documentos.*' => ['integer', 'distinct'],
        ], [
            'documentos.max' => 'Puedes cargar hasta 10 archivos a la vez.',
            'documentos.*.mimes' => 'Cada archivo debe ser JPG, JPEG, PNG, PDF, DOC o DOCX.',
            'documentos.*.max' => 'Cada archivo puede pesar como máximo 10 MB.',
        ]);
    }

    private function guardarDocumentos(AccionEjecucion $accion, array $documentos): void
    {
        foreach ($documentos as $documento) {
            $ruta = $documento->store("acciones/{$accion->id}", 'local');
            $accion->archivos()->create([
                'ruta' => $ruta,
                'nombre_original' => $documento->getClientOriginalName(),
                'tipo_mime' => $documento->getClientMimeType(),
                'tamano' => $documento->getSize(),
            ]);
        }
    }
}

