<?php

namespace Database\Seeders;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\TipoNecesidad;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DemoTariquiaMonitoringSeeder extends Seeder
{
    private const DEMO_SOURCE = '[DEMO] Datos ficticios inspirados en fuentes públicas; no constituyen compromisos oficiales.';

    public function run(): void
    {
        DB::transaction(function (): void {
            $responsables = $this->seedResponsables();
            $tipos = collect([
                'Caminos y accesibilidad',
                'Salud y emergencias',
                'Educación',
                'Agua',
                'Energía',
                'Conectividad y telecomunicaciones',
                'Producción y comercialización',
                'Gestión ambiental y conservación',
                'Turismo comunitario',
                'Gestión institucional y emergencias',
            ])->mapWithKeys(fn (string $nombre) => [
                $nombre => TipoNecesidad::firstOrCreate(['nombre' => $nombre])->id,
            ]);

            $comunidades = Comunidad::pluck('id', 'nombre');
            $themes = $this->themes();
            $states = ['identificada', 'priorizada', 'en_gestion', 'en_ejecucion', 'resuelta', 'postergada'];
            $deadlines = ['2026-09-15', '2026-10-30', '2026-12-15', '2027-02-28', '2027-05-31'];
            $index = 0;

            foreach ($themes as $theme) {
                foreach ($theme['communities'] as $communityName) {
                    $communityId = $comunidades->get($communityName);
                    if (! $communityId) {
                        throw new RuntimeException("No existe la comunidad requerida: {$communityName}");
                    }

                    $state = $states[$index % count($states)];
                    $priority = $index % 5 < 2 ? 'alta' : ($index % 5 < 4 ? 'media' : 'baja');
                    $identifiedAt = Carbon::create(2026, 7, 15)->addDays($index);
                    $deadline = Carbon::parse($deadlines[$index % count($deadlines)]);
                    $title = $theme['title'].' en '.$communityName;

                    $demanda = Demanda::updateOrCreate(
                        ['titulo' => $title, 'fuente' => self::DEMO_SOURCE],
                        [
                            'tipo_necesidad_id' => $tipos[$theme['type']],
                            'descripcion' => $theme['description'].' Registro ficticio creado para probar filtros, mapas, alertas y reportes.',
                            'ubicacion_especifica' => $communityName.' y su área de influencia comunitaria.',
                            'fecha_identificacion' => $identifiedAt->toDateString(),
                            'fecha_limite' => $deadline->toDateString(),
                            'prioridad' => $priority,
                            'estado' => $state,
                            'resultado_esperado' => $theme['result'],
                            'proximo_paso' => $theme['next_step'],
                            'problema_bloqueo' => $state === 'postergada' ? 'Pendiente de coordinación interinstitucional y disponibilidad presupuestaria.' : null,
                        ]
                    );
                    $demanda->comunidades()->sync([$communityId]);

                    $this->seedActions(
                        $demanda,
                        $theme,
                        $responsables,
                        $identifiedAt,
                        $deadline,
                        $index
                    );
                    $index++;
                }
            }
        });
    }

    private function seedResponsables()
    {
        $rows = [
            'vial' => ['Equipo Vial Tariquía (DEMO)', 'Coordinación de mantenimiento vial', 'Caminos y accesibilidad', 'Servicio Departamental de Caminos'],
            'salud' => ['Brigada Sanitaria Tariquía (DEMO)', 'Coordinación de atención primaria', 'Salud', 'Servicio Departamental de Salud'],
            'agua' => ['Unidad de Agua Rural (DEMO)', 'Especialista en agua y saneamiento', 'Agua', 'Gobierno Municipal de Padcaya'],
            'educacion' => ['Equipo Educativo Rural (DEMO)', 'Coordinación de infraestructura educativa', 'Educación', 'Dirección Distrital de Educación'],
            'energia' => ['Unidad de Electrificación Rural (DEMO)', 'Supervisión de energía rural', 'Energía', 'Gobernación de Tarija'],
            'conectividad' => ['Equipo de Conectividad Rural (DEMO)', 'Coordinación de telecomunicaciones', 'Telecomunicaciones', 'Programa de Conectividad Rural'],
            'produccion' => ['Unidad de Desarrollo Productivo (DEMO)', 'Asistencia técnica productiva', 'Producción y comercialización', 'Gobernación de Tarija'],
            'ambiente' => ['Equipo de Gestión Ambiental (DEMO)', 'Monitoreo ambiental participativo', 'Medio ambiente', 'SERNAP'],
            'turismo' => ['Equipo de Turismo Comunitario (DEMO)', 'Promoción de turismo sostenible', 'Turismo', 'Dirección Departamental de Turismo'],
            'gestion' => ['Mesa Interinstitucional Tariquía (DEMO)', 'Coordinación y seguimiento', 'Gestión institucional', 'Gobernación y municipio'],
            'social' => ['Facilitación Comunitaria (DEMO)', 'Enlace con organizaciones comunales', 'Gestión social', 'Comité de Gestión'],
            'emergencias' => ['Unidad de Respuesta Rural (DEMO)', 'Preparación y respuesta a emergencias', 'Emergencias', 'Defensa Civil'],
        ];

        return collect($rows)->mapWithKeys(function (array $row, string $key) {
            [$name, $role, $area, $institution] = $row;
            $responsable = Responsable::updateOrCreate(
                ['email' => $key.'.tariquia@example.org'],
                [
                    'nombre_completo' => $name,
                    'cargo_rol' => $role,
                    'area' => $area,
                    'institucion' => $institution,
                    'telefono' => null,
                    'notas' => 'Registro institucional ficticio para demostración. No representa una designación oficial.',
                    'actualizaciones_registradas' => 'Datos de prueba 2026',
                ]
            );

            return [$key => $responsable];
        });
    }

    private function seedActions(
        Demanda $demanda,
        array $theme,
        $responsables,
        Carbon $identifiedAt,
        Carbon $deadline,
        int $index
    ): void {
        $firstStates = ['completada', 'en_ejecucion', 'pendiente', 'en_ejecucion', 'bloqueada'];
        $secondStates = ['pendiente', 'en_ejecucion', 'pendiente', 'bloqueada', 'en_ejecucion'];
        $firstState = $firstStates[$index % count($firstStates)];
        $secondState = $secondStates[$index % count($secondStates)];
        $firstProgress = ['completada' => 100, 'en_ejecucion' => 55, 'pendiente' => 10, 'bloqueada' => 30][$firstState];
        $secondProgress = ['completada' => 100, 'en_ejecucion' => 45, 'pendiente' => 0, 'bloqueada' => 20][$secondState];

        $actions = [
            [
                'title' => $theme['action_one'],
                'description' => 'Actividad ficticia de diagnóstico y coordinación comunitaria.',
                'state' => $firstState,
                'progress' => $firstProgress,
                'start' => $identifiedAt->copy()->addDays(5),
                'deadline' => $identifiedAt->copy()->addDays(35),
                'next' => 'Validar resultados con la comunidad y documentar acuerdos.',
            ],
            [
                'title' => $theme['action_two'],
                'description' => 'Actividad ficticia de implementación, gestión o seguimiento.',
                'state' => $secondState,
                'progress' => $secondProgress,
                'start' => $identifiedAt->copy()->addDays(25),
                'deadline' => $deadline,
                'next' => $theme['next_step'],
            ],
        ];

        foreach ($actions as $position => $data) {
            $primary = $responsables[$theme['responsible']];
            $action = AccionEjecucion::updateOrCreate(
                ['demanda_id' => $demanda->id, 'titulo' => $data['title']],
                [
                    'responsable_id' => $primary->id,
                    'descripcion' => $data['description'],
                    'estado' => $data['state'],
                    'avance' => $data['progress'],
                    'ultima_actualizacion_avance' => Carbon::create(2026, 9, 20)->subDays(($index + $position) % 24),
                    'fecha_inicio' => $data['start']->toDateString(),
                    'fecha_limite' => $data['deadline']->toDateString(),
                    'resultado_esperado' => $theme['result'],
                    'proximo_paso' => $data['next'],
                    'resultado' => $data['state'] === 'completada' ? 'Diagnóstico demostrativo concluido y registrado.' : null,
                    'evidencias' => null,
                ]
            );

            $assigned = [$primary->id, $responsables['social']->id];
            if ($theme['responsible'] === 'gestion') {
                $assigned[] = $responsables['emergencias']->id;
            }
            $action->responsables()->sync(array_values(array_unique($assigned)));
        }
    }

    private function themes(): array
    {
        return [
            [
                'type' => 'Caminos y accesibilidad', 'responsible' => 'vial',
                'communities' => ['Acherales', 'San Pedro', 'Chillaguatas', 'Río Conchas', 'La Planchada'],
                'title' => 'Mejoramiento de accesibilidad vial',
                'description' => 'Evaluación y atención de tramos con derrumbes, barro, drenaje insuficiente y pasos críticos durante la época de lluvias.',
                'result' => 'Acceso comunitario más seguro y continuidad de tránsito durante todo el año.',
                'next_step' => 'Priorizar tramos críticos y programar maquinaria y obras de drenaje.',
                'action_one' => 'Levantar inventario de puntos críticos', 'action_two' => 'Programar mantenimiento y drenajes',
            ],
            [
                'type' => 'Agua', 'responsible' => 'agua',
                'communities' => ['San José', 'Acheralitos', 'Pampa Grande', 'Loma Alta', 'Chajllas'],
                'title' => 'Fortalecimiento del sistema comunitario de agua',
                'description' => 'Revisión de captación, almacenamiento, distribución, calidad del agua y medidas de protección de fuentes.',
                'result' => 'Sistema comunitario con abastecimiento más seguro y plan de mantenimiento.',
                'next_step' => 'Definir solución técnica y cronograma comunitario de mantenimiento.',
                'action_one' => 'Realizar diagnóstico de agua y saneamiento', 'action_two' => 'Gestionar mejoras de captación y distribución',
            ],
            [
                'type' => 'Salud y emergencias', 'responsible' => 'salud',
                'communities' => ['San José', 'Volcán Blanco', 'Pampa Grande', 'Salinas', 'Pampa Redonda'],
                'title' => 'Mejora de atención primaria y emergencias',
                'description' => 'Fortalecimiento de brigadas, botiquines, referencia de pacientes, comunicación y equipamiento básico.',
                'result' => 'Atención primaria periódica y protocolo de respuesta ante emergencias.',
                'next_step' => 'Coordinar calendario de brigadas y dotación priorizada de insumos.',
                'action_one' => 'Actualizar diagnóstico sanitario comunitario', 'action_two' => 'Organizar brigadas y ruta de referencia',
            ],
            [
                'type' => 'Educación', 'responsible' => 'educacion',
                'communities' => ['Volcán Blanco', 'Chillaguatas', 'Salinas', 'Pampa Redonda', 'Chiquiacá Centro'],
                'title' => 'Adecuación de infraestructura educativa',
                'description' => 'Revisión de aulas, iluminación, agua, saneamiento, mobiliario y condiciones para permanencia escolar.',
                'result' => 'Unidad educativa con condiciones básicas priorizadas y plan de intervención.',
                'next_step' => 'Validar presupuesto de reparaciones y dotaciones con la dirección distrital.',
                'action_one' => 'Inspeccionar infraestructura y servicios escolares', 'action_two' => 'Gestionar reparaciones y equipamiento',
            ],
            [
                'type' => 'Energía', 'responsible' => 'energia',
                'communities' => ['Puesto Rueda', 'Motoví', 'Acheralitos', 'Loma Alta', 'Tipas'],
                'title' => 'Ampliación y mantenimiento de energía rural',
                'description' => 'Evaluación de cobertura, paneles solares, baterías, medidores y alternativas de suministro sostenible.',
                'result' => 'Solución energética priorizada con responsabilidades y mantenimiento definidos.',
                'next_step' => 'Completar evaluación técnica y asegurar reposición de componentes.',
                'action_one' => 'Inventariar sistemas y hogares sin cobertura', 'action_two' => 'Formular solución de electrificación',
            ],
            [
                'type' => 'Conectividad y telecomunicaciones', 'responsible' => 'conectividad',
                'communities' => ['Tipas', 'Chiquiacá Norte', 'Chajllas', 'Piedra Grande', 'El Cajón'],
                'title' => 'Mejora de conectividad y comunicación rural',
                'description' => 'Medición de cobertura móvil y alternativas de comunicación para educación, salud y emergencias.',
                'result' => 'Mapa de cobertura y alternativa viable de conectividad comunitaria.',
                'next_step' => 'Coordinar pruebas técnicas y acuerdos con operadores o redes comunitarias.',
                'action_one' => 'Medir señal y puntos de conectividad', 'action_two' => 'Gestionar solución de comunicación comunitaria',
            ],
            [
                'type' => 'Producción y comercialización', 'responsible' => 'produccion',
                'communities' => ['Motoví', 'Pampa Grande', 'Salinas', 'Chiquiacá Sur', 'Piedra Grande'],
                'title' => 'Fortalecimiento de producción y comercialización',
                'description' => 'Asistencia técnica para apicultura, agricultura, ganadería, transformación y acceso a mercados.',
                'result' => 'Plan productivo comunitario con asistencia, calendario y canales comerciales.',
                'next_step' => 'Priorizar cadenas productivas y organizar asistencia técnica especializada.',
                'action_one' => 'Caracterizar productores y cadenas de valor', 'action_two' => 'Implementar asistencia y estrategia comercial',
            ],
            [
                'type' => 'Gestión ambiental y conservación', 'responsible' => 'ambiente',
                'communities' => ['Chiquiacá Norte', 'Chiquiacá Centro', 'Chiquiacá Sur', 'La Misión', 'Loma Alta'],
                'title' => 'Monitoreo comunitario de agua y biodiversidad',
                'description' => 'Seguimiento participativo de fuentes de agua, bosque, fauna, riesgos ambientales y cumplimiento del plan de manejo.',
                'result' => 'Línea base comunitaria y protocolo periódico de monitoreo ambiental.',
                'next_step' => 'Acordar indicadores, puntos de muestreo y mecanismo de reporte comunitario.',
                'action_one' => 'Definir puntos de monitoreo ambiental', 'action_two' => 'Ejecutar campañas comunitarias de seguimiento',
            ],
            [
                'type' => 'Turismo comunitario', 'responsible' => 'turismo',
                'communities' => ['Río Conchas', 'La Planchada', 'Pampa Grande', 'Salinas', 'Piedra Grande'],
                'title' => 'Desarrollo de turismo comunitario sostenible',
                'description' => 'Diseño de rutas, capacidades locales, seguridad, interpretación ambiental y promoción responsable.',
                'result' => 'Producto turístico piloto gestionado por la comunidad y compatible con la conservación.',
                'next_step' => 'Validar ruta piloto, capacidad de carga y esquema de beneficios comunitarios.',
                'action_one' => 'Inventariar atractivos y servicios locales', 'action_two' => 'Diseñar ruta y protocolo de visitantes',
            ],
            [
                'type' => 'Gestión institucional y emergencias', 'responsible' => 'gestion',
                'communities' => ['San Pedro', 'Chillaguatas', 'El Cajón', 'Acherales', 'Chiquiacá Norte'],
                'title' => 'Plan comunitario de preparación ante emergencias',
                'description' => 'Organización de alertas, rutas de evacuación, contactos, puntos seguros y coordinación ante lluvias, incendios o aislamiento.',
                'result' => 'Plan comunitario de emergencia probado y actualizado.',
                'next_step' => 'Realizar simulacro y corregir responsabilidades y rutas de respuesta.',
                'action_one' => 'Elaborar mapa comunitario de riesgos', 'action_two' => 'Organizar protocolo y simulacro de respuesta',
            ],
        ];
    }
}
