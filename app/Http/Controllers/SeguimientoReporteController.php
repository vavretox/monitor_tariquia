<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\TipoNecesidad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SeguimientoReporteController extends Controller
{
    public function ejecutivo(Request $request)
    {
        $demandas = $this->demandasFiltradas($request)->get();
        $tipos = TipoNecesidad::orderBy('nombre')->get();
        $comunidades = Comunidad::orderBy('nombre')->get();

        return view('reportes.demandas', compact('demandas', 'tipos', 'comunidades'));
    }

    public function alertas()
    {
        $base = ['demanda.comunidades', 'responsables'];
        $vencidas = AccionEjecucion::with($base)->where('estado', '!=', 'completada')->whereDate('fecha_limite', '<', today())->orderBy('fecha_limite')->get();
        $proximas = AccionEjecucion::with($base)->where('estado', '!=', 'completada')->whereBetween('fecha_limite', [today(), today()->addDays(15)])->orderBy('fecha_limite')->get();
        $sinActualizar = AccionEjecucion::with($base)->where('estado', '!=', 'completada')->where(fn ($q) => $q->whereNull('ultima_actualizacion_avance')->orWhere('ultima_actualizacion_avance', '<', now()->subDays(30)))->get();
        $sinAcciones = Demanda::with(['comunidades', 'tipo'])->doesntHave('acciones')->get();
        $completadasSinEvidencia = AccionEjecucion::with('demanda.comunidades')->where('estado', 'completada')->doesntHave('archivos')->get();

        return view('reportes.alertas_demandas', compact('vencidas', 'proximas', 'sinActualizar', 'sinAcciones', 'completadasSinEvidencia'));
    }

    public function csv(Request $request)
    {
        $acciones = AccionEjecucion::with(['demanda.comunidades', 'demanda.tipo', 'responsables', 'archivos'])
            ->whereHas('demanda', fn (Builder $query) => $this->aplicarFiltrosDemanda($query, $request))
            ->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado))
            ->when($request->filled('buscar'), fn ($query) => $query->where(fn ($query) => $query
                ->where('titulo', 'like', '%'.$request->buscar.'%')
                ->orWhereHas('responsables', fn ($responsables) => $responsables->where('nombre_completo', 'like', '%'.$request->buscar.'%'))
                ->orWhereHas('demanda', fn ($demanda) => $demanda->where('titulo', 'like', '%'.$request->buscar.'%'))))
            ->get();

        return response()->streamDownload(function () use ($acciones) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Comunidades', 'Tipo o sector', 'Demanda', 'Acción', 'Responsables', 'Estado', 'Fecha límite', 'Próximo paso', 'Evidencias'], ';');
            foreach ($acciones as $accion) {
                fputcsv($file, [$accion->demanda?->comunidades?->pluck('nombre')->join(', '), $accion->demanda?->tipo?->nombre ?? 'Sin clasificar', $accion->demanda?->titulo, $accion->titulo, $accion->responsables->pluck('nombre_completo')->join(', '), $accion->estado, $accion->fecha_limite?->format('d/m/Y'), $accion->proximo_paso, $accion->archivos->count()], ';');
            }
            fclose($file);
        }, 'matriz-demandas-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function demandasFiltradas(Request $request): Builder
    {
        return Demanda::with([
            'comunidades', 'tipo',
            'acciones' => fn ($query) => $query->when($request->filled('estado'), fn ($query) => $query->where('estado', $request->estado)),
            'acciones.responsables', 'acciones.archivos',
        ])->where(fn (Builder $query) => $this->aplicarFiltrosDemanda($query, $request))
            ->when($request->filled('estado'), fn ($query) => $query->whereHas('acciones', fn ($acciones) => $acciones->where('estado', $request->estado)))
            ->when($request->filled('buscar'), fn ($query) => $query->where(fn ($query) => $query
                ->where('titulo', 'like', '%'.$request->buscar.'%')
                ->orWhere('descripcion', 'like', '%'.$request->buscar.'%')
                ->orWhereHas('acciones', fn ($acciones) => $acciones->where('titulo', 'like', '%'.$request->buscar.'%')
                    ->orWhereHas('responsables', fn ($responsables) => $responsables->where('nombre_completo', 'like', '%'.$request->buscar.'%')))))
            ->orderBy('titulo');
    }

    private function aplicarFiltrosDemanda(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->tipo_necesidad_id === '__sin_clasificar__', fn ($query) => $query->whereNull('tipo_necesidad_id'))
            ->when($request->filled('tipo_necesidad_id') && $request->tipo_necesidad_id !== '__sin_clasificar__', fn ($query) => $query->where('tipo_necesidad_id', $request->tipo_necesidad_id))
            ->when($request->filled('comunidad_id'), fn ($query) => $query->whereHas('comunidades', fn ($comunidades) => $comunidades->where('comunidades.id', $request->comunidad_id)));
    }
}
