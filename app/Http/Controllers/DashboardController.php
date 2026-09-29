<?php
namespace App\Http\Controllers;
use App\Models\Comunidad;use App\Models\Necesidad;use App\Models\AccionCompromiso;use App\Models\AccionEjecucion;use App\Models\Responsable;
class DashboardController extends Controller{public function index(){
 $comunidades=Comunidad::with(['necesidadesRelacionadas.compromisos.accionesEjecucion.responsables','necesidadesRelacionadas.compromisos.responsablePrincipal','accionesCompromisos.accionesEjecucion.responsables'])->orderBy('nombre')->get();
 $stats=['comunidades'=>$comunidades->count(),'necesidades'=>Necesidad::count(),'compromisos'=>AccionCompromiso::count(),'acciones'=>AccionEjecucion::count(),'responsables'=>Responsable::count(),'cumplidas'=>AccionEjecucion::where('estado','completada')->count(),'bloqueadas'=>AccionEjecucion::where('estado','bloqueada')->count(),'vencidas'=>AccionEjecucion::where('estado','!=','completada')->whereDate('fecha_limite','<',today())->count(),'sin_compromiso'=>Necesidad::doesntHave('compromisos')->count(),'sin_actualizar'=>AccionEjecucion::where('estado','!=','completada')->where(fn($q)=>$q->whereNull('ultima_actualizacion_avance')->orWhere('ultima_actualizacion_avance','<',now()->subDays(30)))->count()];
 $incumplimientos=AccionEjecucion::with('responsables')->where('estado','!=','completada')->whereDate('fecha_limite','<',today())->get();
 $incumplimientosPorResponsable=$incumplimientos->flatMap(fn($accion)=>$accion->responsables->map(fn($responsable)=>['responsable'=>$responsable->nombre_completo,'accion'=>$accion->titulo,'dias'=>$accion->dias_retraso,'estado'=>$accion->estado]))->sortByDesc('dias')->take(10)->values();
 $porEstado=AccionEjecucion::selectRaw('estado,count(*) total')->groupBy('estado')->pluck('total','estado');
 $porArea=AccionCompromiso::selectRaw('area,count(*) total')->whereNotNull('area')->groupBy('area')->orderByDesc('total')->pluck('total','area');
 $porTerritorio=Comunidad::selectRaw('territorio,count(*) total')->groupBy('territorio')->pluck('total','territorio');
 $proximas=AccionEjecucion::with(['compromiso.comunidad','responsables'])->whereNotNull('fecha_limite')->where('estado','!=','completada')->orderBy('fecha_limite')->limit(6)->get();
 $publico=auth()->guest();
 $mapaComunidades=$comunidades->map(fn($comunidad)=>[
  'id'=>$comunidad->id,'nombre'=>$comunidad->nombre,'territorio'=>$comunidad->territorio,'latitud'=>$comunidad->latitud,'longitud'=>$comunidad->longitud,
  'necesidades_relacionadas'=>$comunidad->necesidadesRelacionadas->map(fn($necesidad)=>[
   'titulo'=>$necesidad->titulo,'descripcion'=>$necesidad->descripcion,'prioridad'=>$necesidad->prioridad,
   'compromisos'=>$necesidad->compromisos->map(fn($compromiso)=>[
    'titulo'=>$compromiso->titulo,'area'=>$compromiso->area,'estado'=>$compromiso->estado,
    'acciones_ejecucion'=>$compromiso->accionesEjecucion->map(fn($accion)=>[
     'titulo'=>$accion->titulo,'estado'=>$accion->estado,
     'responsables'=>$publico ? [] : $accion->responsables->map(fn($responsable)=>['nombre_completo'=>$responsable->nombre_completo])->values(),
    ])->values(),
   ])->values(),
  ])->values(),
 ]);
 return view('dashboard',compact('comunidades','mapaComunidades','stats','porEstado','porArea','porTerritorio','proximas','publico','incumplimientosPorResponsable'));
}}
