<?php
namespace App\Exports;
use App\Models\Proyecto;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProyectosExport implements FromCollection, WithHeadings {
    public function collection() {
        return Proyecto::select('id','nombre','tipo','estado','latitud','longitud','presupuesto','fecha_inicio','fecha_fin_estimada')->get();
    }
    public function headings(): array {
        return ['ID','Nombre','Tipo','Estado','Latitud','Longitud','Presupuesto','Fecha inicio','Fecha fin estimada'];
    }
}
