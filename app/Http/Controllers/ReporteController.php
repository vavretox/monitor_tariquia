<?php
namespace App\Http\Controllers;
use App\Models\Proyecto;
use Barryvdh\DomPDF\Facade\Pdf;
use Spatie\Activitylog\Models\Activity;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller {
    public function pdf() {
        $proyectos = Proyecto::all();
        $pdf = Pdf::loadView('reportes.proyectos_pdf', compact('proyectos'));
        return $pdf->download('proyectos_tariquia.pdf');
    }
    public function excel() {
        return Excel::download(new \App\Exports\ProyectosExport, 'proyectos_tariquia.xlsx');
    }
    public function historial() {
        $activities = Activity::with('causer')->latest()->paginate(30);
        return view('reportes.historial', compact('activities'));
    }
}
