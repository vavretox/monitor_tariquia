<?php

namespace App\Http\Controllers;

use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\TipoNecesidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NecesidadController extends Controller
{
    public function index(Request $request)
    {
        $necesidades = Necesidad::with(['comunidades', 'tipo'])->withCount('compromisos')
            ->when($request->buscar, fn ($query, $value) => $query->where(fn ($query) => $query
                ->where('titulo', 'like', "%$value%")
                ->orWhere('descripcion', 'like', "%$value%")))
            ->when($request->comunidad_id, fn ($query, $value) => $query->whereHas('comunidades', fn ($query) => $query->where('comunidades.id', $value)))
            ->when($request->tipo_necesidad_id, fn ($query, $value) => $query->where('tipo_necesidad_id', $value))
            ->when($request->estado, fn ($query, $value) => $query->where('estado', $value))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('necesidades.index', [
            'necesidades' => $necesidades,
            'comunidades' => Comunidad::orderBy('nombre')->get(),
            'tiposNecesidad' => TipoNecesidad::orderBy('nombre')->get(),
        ]);
    }

    public function show(Necesidad $necesidade)
    {
        $necesidade->load(['tipo', 'comunidades', 'compromisos.accionesEjecucion.responsables']);

        return view('necesidades.show', ['necesidad' => $necesidade]);
    }

    public function create(Request $request)
    {
        return view('necesidades.create', [
            'comunidades' => Comunidad::orderBy('nombre')->get(),
            'tiposNecesidad' => TipoNecesidad::orderBy('nombre')->get(),
            'comunidadId' => $request->comunidad_id,
            'returnTo' => $request->return_to,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validar($request);
        $communityIds = $data['comunidad_ids'];
        $tipoId = $this->resolverTipo($data);
        unset($data['comunidad_ids']);
        $data['comunidad_id'] = $communityIds[0];
        $data['tipo_necesidad_id'] = $tipoId;

        $necesidad = DB::transaction(function () use ($data, $communityIds) {
            $necesidad = Necesidad::create($data);
            $necesidad->comunidades()->sync($communityIds);

            return $necesidad;
        });

        if ($request->return_to === 'compromisos.create') {
            return redirect()->route('compromisos.create', ['necesidad_id' => $necesidad->id])
                ->with('success', 'Necesidad registrada. Ya puede crear el compromiso.');
        }

        return redirect()->route('necesidades.show', $necesidad)->with('success', 'Necesidad registrada.');
    }

    public function edit(Necesidad $necesidade)
    {
        $necesidade->load(['comunidades', 'tipo']);

        return view('necesidades.edit', [
            'necesidad' => $necesidade,
            'comunidades' => Comunidad::orderBy('nombre')->get(),
            'tiposNecesidad' => TipoNecesidad::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Necesidad $necesidade)
    {
        $data = $this->validar($request);
        $communityIds = $data['comunidad_ids'];
        $tipoId = $this->resolverTipo($data);
        unset($data['comunidad_ids']);
        $data['comunidad_id'] = $communityIds[0];
        $data['tipo_necesidad_id'] = $tipoId;

        DB::transaction(function () use ($necesidade, $data, $communityIds) {
            $necesidade->update($data);
            $necesidade->comunidades()->sync($communityIds);
        });

        return redirect()->route('necesidades.show', $necesidade)->with('success', 'Necesidad actualizada.');
    }

    public function destroy(Necesidad $necesidade)
    {
        $necesidade->delete();

        return redirect()->route('necesidades.index')->with('success', 'Necesidad eliminada.');
    }

    private function validar(Request $request): array
    {
        $tipoRules = $request->input('tipo_necesidad_id') === '__nuevo__'
            ? ['required', 'in:__nuevo__']
            : ['required', 'integer', 'exists:tipos_necesidad,id'];

        return $request->validate([
            'comunidad_ids' => ['required', 'array', 'min:1'],
            'comunidad_ids.*' => ['integer', 'distinct', 'exists:comunidades,id'],
            'tipo_necesidad_id' => $tipoRules,
            'tipo_necesidad_nuevo' => ['nullable', 'required_if:tipo_necesidad_id,__nuevo__', 'string', 'max:255'],
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'ubicacion_especifica' => ['nullable', 'string', 'max:255'],
            'fuente' => ['nullable', 'string', 'max:255'],
            'fecha_identificacion' => ['nullable', 'date'],
            'prioridad' => ['required', 'in:baja,media,alta,critica'],
            'estado' => ['required', 'in:identificada,en_atencion,resuelta,postergada'],
        ], [
            'tipo_necesidad_id.required' => 'Seleccione el tipo de necesidad.',
            'tipo_necesidad_nuevo.required_if' => 'Escriba el nombre del nuevo tipo de necesidad.',
        ]);
    }

    private function resolverTipo(array &$data): int
    {
        if ($data['tipo_necesidad_id'] === '__nuevo__') {
            $tipo = TipoNecesidad::firstOrCreate(['nombre' => trim($data['tipo_necesidad_nuevo'])]);
            $tipoId = $tipo->id;
        } else {
            $tipoId = (int) $data['tipo_necesidad_id'];
        }

        unset($data['tipo_necesidad_id'], $data['tipo_necesidad_nuevo']);

        return $tipoId;
    }
}