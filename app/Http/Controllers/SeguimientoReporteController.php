<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Necesidad;

class SeguimientoReporteController extends Controller
{
    public function ejecutivo()
    {
        $necesidades = Necesidad::with(['comunidades', 'tipo', 'compromisos.accionesEjecucion.responsables', 'compromisos.accionesEjecucion.archivos'])
            ->orderBy('titulo')->get();
        return view('reportes.seguimiento', compact('necesidades'));
    }

    public function alertas()
    {
        $vencidas = AccionEjecucion::with(['compromiso.necesidad.comunidades', 'responsables'])->whereNotIn('estado', ['completada'])->whereDate('fecha_limite', '<', today())->orderBy('fecha_limite')->get();
        $proximas = AccionEjecucion::with(['compromiso.necesidad.comunidades', 'responsables'])->whereNotIn('estado', ['completada'])->whereBetween('fecha_limite', [today(), today()->addDays(15)])->orderBy('fecha_limite')->get();
        $sinActualizar = AccionEjecucion::with(['compromiso.necesidad.comunidades', 'responsables'])->whereNotIn('estado', ['completada'])->where(fn ($q) => $q->whereNull('ultima_actualizacion_avance')->orWhere('ultima_actualizacion_avance', '<', now()->subDays(30)))->get();
        $sinCompromiso = Necesidad::with(['comunidades', 'tipo'])->doesntHave('compromisos')->get();
        $completadasSinEvidencia = AccionEjecucion::with(['compromiso.necesidad.comunidades'])->where('estado', 'completada')->doesntHave('archivos')->get();
        return view('reportes.alertas', compact('vencidas', 'proximas', 'sinActualizar', 'sinCompromiso', 'completadasSinEvidencia'));
    }

    public function csv()
    {
        $acciones = AccionEjecucion::with(['compromiso.necesidad.comunidades', 'responsables'])->get();
        return response()->streamDownload(function () use ($acciones) {
            $f = fopen('php://output', 'w');
            fprintf($f, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($f, ['Comunidades', 'Necesidad', 'Compromiso', 'Acción', 'Responsables', 'Estado', 'Avance', 'Fecha límite', 'Próximo paso', 'Evidencias'], ';');
            foreach ($acciones as $a) fputcsv($f, [$a->compromiso?->necesidad?->comunidades?->pluck('nombre')->join(', '), $a->compromiso?->necesidad?->titulo, $a->compromiso?->titulo, $a->titulo, $a->responsables->pluck('nombre_completo')->join(', '), $a->estado, $a->avance.'%', $a->fecha_limite?->format('d/m/Y'), $a->proximo_paso, $a->archivos()->count()], ';');
            fclose($f);
        }, 'matriz-seguimiento-tariquia-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}