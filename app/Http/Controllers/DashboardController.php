<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\TipoNecesidad;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index()
    {
        $comunidades = Comunidad::with(['demandas.tipo', 'demandas.acciones:id,demanda_id,estado,fecha_limite'])->orderBy('nombre')->get();
        $stats = ['comunidades' => $comunidades->count(), 'demandas' => Demanda::count(), 'acciones' => AccionEjecucion::count(), 'responsables' => Responsable::count(), 'cumplidas' => AccionEjecucion::where('estado', 'completada')->count(), 'bloqueadas' => AccionEjecucion::where('estado', 'bloqueada')->count(), 'vencidas' => AccionEjecucion::where('estado', '!=', 'completada')->whereDate('fecha_limite', '<', today())->count(), 'sin_acciones' => Demanda::doesntHave('acciones')->count(), 'sin_actualizar' => AccionEjecucion::where('estado', '!=', 'completada')->where(fn ($query) => $query->whereNull('ultima_actualizacion_avance')->orWhere('ultima_actualizacion_avance', '<', now()->subDays(30)))->count()];
        $incumplimientos = AccionEjecucion::with('responsables')->where('estado', '!=', 'completada')->whereDate('fecha_limite', '<', today())->get();
        $incumplimientosPorResponsable = $incumplimientos->flatMap(fn ($accion) => $accion->responsables->map(fn ($responsable) => ['responsable' => $responsable->nombre_completo, 'accion' => $accion->titulo, 'dias' => $accion->dias_retraso, 'estado' => $accion->estado]))->sortByDesc('dias')->take(10)->values();
        $porEstado = AccionEjecucion::selectRaw('estado,count(*) total')->groupBy('estado')->pluck('total', 'estado');
        $porArea = Demanda::join('tipos_necesidad', 'tipos_necesidad.id', '=', 'demandas.tipo_necesidad_id')->selectRaw('tipos_necesidad.nombre as area,count(*) total')->groupBy('tipos_necesidad.nombre')->orderByDesc('total')->pluck('total', 'area');
        $porTerritorio = Comunidad::selectRaw('territorio,count(*) total')->groupBy('territorio')->pluck('total', 'territorio');
        $tiposDemanda = TipoNecesidad::orderBy('nombre')->get(['id', 'nombre']);
        $proximas = AccionEjecucion::with(['demanda.comunidades', 'responsables'])->whereNotNull('fecha_limite')->where('estado', '!=', 'completada')->orderBy('fecha_limite')->limit(6)->get();
        $publico = auth()->guest();
        $mapaComunidades = $comunidades->map(fn ($comunidad) => ['id' => $comunidad->id, 'nombre' => $comunidad->nombre, 'territorio' => $comunidad->territorio, 'latitud' => $comunidad->latitud, 'longitud' => $comunidad->longitud, 'detail_url' => route('mapa.comunidades.show', $comunidad), 'url' => $publico ? null : route('comunidades.show', $comunidad), 'demandas' => $comunidad->demandas->map(fn ($demanda) => ['id' => $demanda->id, 'titulo' => $demanda->titulo, 'prioridad' => $demanda->prioridad, 'estado' => $demanda->estado, 'tipo_id' => $demanda->tipo_necesidad_id, 'tipo' => $demanda->tipo?->nombre ?? 'Sin clasificar', 'acciones' => $demanda->acciones->map(fn ($accion) => ['estado' => $accion->estado, 'vencida' => $accion->dias_retraso > 0])->values()])->values()]);

        return view('dashboard', compact('comunidades', 'mapaComunidades', 'stats', 'porEstado', 'porArea', 'porTerritorio', 'tiposDemanda', 'proximas', 'publico', 'incumplimientosPorResponsable'));
    }

    public function community(Comunidad $comunidad): JsonResponse
    {
        $comunidad->load(['demandas.tipo', 'demandas.acciones.responsables']);
        $publico = auth()->guest();

        return response()->json(['id' => $comunidad->id, 'nombre' => $comunidad->nombre, 'territorio' => $comunidad->territorio, 'latitud' => $comunidad->latitud, 'longitud' => $comunidad->longitud, 'url' => $publico ? null : route('comunidades.show', $comunidad), 'demandas' => $comunidad->demandas->map(fn ($demanda) => ['id' => $demanda->id, 'titulo' => $demanda->titulo, 'descripcion' => $demanda->descripcion, 'prioridad' => $demanda->prioridad, 'estado' => $demanda->estado, 'tipo_id' => $demanda->tipo_necesidad_id, 'tipo' => $demanda->tipo?->nombre ?? 'Sin clasificar', 'url' => $publico ? null : route('demandas.show', $demanda), 'acciones' => $demanda->acciones->map(fn ($accion) => ['titulo' => $accion->titulo, 'estado' => $accion->estado, 'avance' => (int) ($accion->avance ?? 0), 'fecha_limite' => $accion->fecha_limite?->format('d/m/Y'), 'vencida' => $accion->dias_retraso > 0, 'responsables' => $publico ? [] : $accion->responsables->map(fn ($responsable) => ['nombre_completo' => $responsable->nombre_completo])->values()])->values()])->values()]);
    }
}
