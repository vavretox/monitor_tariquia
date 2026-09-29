<?php
namespace Database\Seeders;
use App\Models\Proyecto;
use Illuminate\Database\Seeder;

class ProyectoSeeder extends Seeder {
    public function run(): void {
        $rows = [
            ['Puesto de Salud San Diego','salud','en_ejecucion',-21.9500,-64.3200,150000],
            ['Camino Tariquía – Salinas','infraestructura_caminos','planificado',-22.0050,-64.3450,480000],
            ['Puente sobre Río Tariquía','infraestructura_caminos','completado',-21.9700,-64.3800,320000],
            ['Posta Sanitaria La Merced','salud','completado',-22.0200,-64.2900,90000],
            ['Camino Chiquiacá – Tariquía','infraestructura_caminos','suspendido',-21.9400,-64.3600,260000],
        ];
        foreach ($rows as [$n,$t,$e,$lat,$lng,$pres]) {
            Proyecto::create([
                'nombre' => $n,
                'descripcion' => "Proyecto de {$t} en la Reserva de Tariquía.",
                'tipo' => $t, 'estado' => $e,
                'latitud' => $lat, 'longitud' => $lng,
                'presupuesto' => $pres,
            ]);
        }
    }
}
