<?php

namespace Database\Seeders;

use App\Models\AccionCompromiso;
use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\Responsable;
use App\Models\TipoNecesidad;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CasosRealesDocumentosSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->crearCaso([
                'tipo' => 'Caminos y accesibilidad',
                'comunidades' => ['San Pedro', 'Chillaguatas', 'Acheralitos'],
                'necesidad' => 'Apertura y continuidad del camino San Pedro – Chillaguatas – Acheralitos',
                'descripcion' => 'La falta de continuidad vial dificulta el acceso, la atención de emergencias y la salida de productos; en algunos sectores el traslado se realiza a pie, a caballo o al hombro.',
                'ubicacion' => 'Tramos San Pedro – Chillaguatas y Chillaguatas – Acheralitos',
                'fuente' => 'Primer Insumo del Plan Tariquía, visita territorial del 7 y 8 de agosto de 2026; referencias 4.0.2, 4.2.1 y 4.0.6.',
                'fecha' => '2026-08-07',
                'compromiso' => 'Gestionar la apertura de los tramos San Pedro – Chillaguatas y Chillaguatas – Acheralitos',
                'area' => 'Caminos y Accesibilidad',
                'institucion' => 'Subgobernación y área legal',
                'accion' => 'Revisar las restricciones ambientales y legales que dificultan la apertura de los caminos',
                'resultado' => 'Ruta de gestión definida para avanzar en la apertura de los tramos.',
                'responsables' => ['SERNAP / Área Legal'],
            ]);

            $this->crearCaso([
                'tipo' => 'Salud y emergencias',
                'comunidades' => ['Volcán Blanco', 'San José', 'Motoví'],
                'necesidad' => 'Asignación de personal médico para Volcán Blanco, San José y Motoví',
                'descripcion' => 'Los establecimientos reportan personal insuficiente y requieren ítems de salud para garantizar atención médica continua en las comunidades.',
                'ubicacion' => 'Centros y postas de salud de Volcán Blanco, San José y Motoví',
                'fuente' => 'Primer Insumo del Plan Tariquía, visita territorial del 7 y 8 de agosto de 2026; referencias 2.4.3, 1.4.3 y 3.11.3.',
                'fecha' => '2026-08-07',
                'compromiso' => 'Gestionar la asignación de tres ítems de salud pendientes',
                'area' => 'Salud',
                'institucion' => 'Gobierno Municipal y SEDES',
                'accion' => 'Verificar y gestionar los ítems de salud requeridos por las tres comunidades',
                'resultado' => 'Personal de salud disponible para la atención.',
                'responsables' => ['SEDES'],
            ]);

            $this->crearCaso([
                'tipo' => 'Agua',
                'comunidades' => ['Salinas', 'Pampa Redonda', 'Loma Alta'],
                'necesidad' => 'Restitución de agua segura para escuelas y postas de Salinas, Pampa Redonda y Loma Alta',
                'descripcion' => 'Se reportaron sistemas dañados o inexistentes, consumo directo del río y falta de agua en establecimientos educativos y de salud.',
                'ubicacion' => 'Unidad educativa de Salinas y establecimientos mencionados en Pampa Redonda y Loma Alta, con puntos exactos por verificar',
                'fuente' => 'Segundo Insumo del Plan Tariquía, segunda visita territorial de septiembre de 2026; referencias 2.1, 4.3–4.4 y acción inmediata de agua.',
                'fecha' => null,
                'compromiso' => 'Diagnosticar los sistemas de agua dañados o inexistentes y priorizar soluciones rápidas',
                'area' => 'Agua',
                'institucion' => 'Gobierno Municipal, Gobernación y comunidades',
                'accion' => 'Realizar diagnóstico técnico y definir una solución provisional o restitución del servicio',
                'resultado' => 'Agua segura restituida o solución provisional definida.',
                'responsables' => ['Dirección de Recursos Hídricos / Gobierno Municipal'],
            ]);
        });
    }

    private function crearCaso(array $caso): void
    {
        $comunidades = Comunidad::whereIn('nombre', $caso['comunidades'])->get();
        if ($comunidades->count() !== count($caso['comunidades'])) {
            throw new \RuntimeException('Faltan comunidades para registrar el caso: '.$caso['necesidad']);
        }

        $tipo = TipoNecesidad::firstOrCreate(['nombre' => $caso['tipo']]);
        $necesidad = Necesidad::firstOrCreate(
            ['titulo' => $caso['necesidad']],
            [
                'comunidad_id' => $comunidades->first()->id,
                'tipo_necesidad_id' => $tipo->id,
                'descripcion' => $caso['descripcion'],
                'ubicacion_especifica' => $caso['ubicacion'],
                'fuente' => $caso['fuente'],
                'fecha_identificacion' => $caso['fecha'],
                'prioridad' => 'alta',
                'estado' => 'identificada',
            ]
        );
        $necesidad->comunidades()->syncWithoutDetaching($comunidades->pluck('id'));

        $compromiso = AccionCompromiso::firstOrCreate(
            ['necesidad_id' => $necesidad->id, 'titulo' => $caso['compromiso']],
            [
                'comunidad_id' => $comunidades->first()->id,
                'descripcion' => 'Compromiso identificado en el documento territorial y sujeto a seguimiento institucional.',
                'area' => $caso['area'],
                'prioridad' => 'alta',
                'fecha_compromiso' => $caso['fecha'],
                'estado' => 'planificado',
                'porcentaje' => 0,
                'proximo_paso' => $caso['accion'],
                'resultado_esperado' => $caso['resultado'],
                'institucion_responsable' => $caso['institucion'],
            ]
        );

        $responsables = collect($caso['responsables'])->map(fn (string $nombre) => Responsable::firstOrCreate(
            ['nombre_completo' => $nombre],
            ['institucion' => $caso['institucion'], 'area' => $caso['area']]
        ));
        $compromiso->responsables()->syncWithoutDetaching($responsables->pluck('id'));

        $accion = AccionEjecucion::firstOrCreate(
            ['accion_compromiso_id' => $compromiso->id, 'titulo' => $caso['accion']],
            [
                'responsable_id' => $responsables->first()?->id,
                'descripcion' => 'Acción inmediata identificada para activar el compromiso.',
                'resultado_esperado' => $caso['resultado'],
                'estado' => 'pendiente',
                'avance' => 0,
                'proximo_paso' => 'Confirmar responsable operativo, alcance técnico y cronograma.',
            ]
        );
        $accion->responsables()->syncWithoutDetaching($responsables->pluck('id'));
        $accion->historialAvances()->firstOrCreate(
            ['avance' => 0, 'estado' => 'pendiente'],
            ['comentario' => 'Caso inicial cargado desde los documentos territoriales.']
        );
    }
}
