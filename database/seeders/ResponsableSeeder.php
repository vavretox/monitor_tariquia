<?php

namespace Database\Seeders;

use App\Models\Responsable;
use Illuminate\Database\Seeder;

class ResponsableSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['Juan Pablo Pérez Mendoza', 'Coordinador Regional de Salud', 'Salud', '+591 71234567', 'jp.perez@ministerio-salud.gob.bo', 'Encargado de la supervisión de centros de salud.', 'ACT-13', 'Subgobernación', 1],
            ['María Elena García Choque', 'Directora Distrital de Educación', 'Educación', '+591 72345678', 'mgarcia@educacion.gob.bo', 'Responsable de la implementación del nuevo currículo.', 'ACT-14', 'Subgobernación', 2],
            ['Carlos Alberto Mamani Quispe', 'Jefe Técnico de Recursos Hídricos', 'Agua', '+591 73456789', 'carlos.mamani@aguas-regionales.org', 'Especialista en sistemas de riego y saneamiento básico.', 'ACT-15', 'Servicio Departamental de Caminos (SEDECA)', 3],
            ['Elsa Ponce Oblitas', 'Directora de Hidrocarburos, Energía y Minería GADT', 'Energía', null, null, 'Supervisa la expansión de la red eléctrica en comunidades.', 'ACT-16', null, 4],
            ['Roberto Carlos Flores Sufiagua', 'Ingeniero Supervisor de Caminos', 'Caminos y Accesibilidad', '+591 75678901', 'r-flores@abe.gob.bo', 'Responsable del mantenimiento preventivo de la red vial.', 'ACT-17', 'Gobierno Autónomo Departamental de Tarija (GADT)', 5],
            ['Lucía Fernanda Quispe Ticona', 'Gestora de Conectividad Digital', 'Telecomunicaciones', '+591 76789012', 'lquispe@telecom.gob.bo', 'Coordina la instalación de antenas de telefonía móvil.', 'ACT-18', null, 6],
            ['Susana Cardozo Martínez', 'Directora de Desarrollo Económico y Productivo GADT', 'Producción y Comercialización', null, null, 'Apoyo técnico a las asociaciones de productores.', 'ACT-19', null, 7],
            ['Carlos Eduardo Bares Ayala', 'Secretaría de Turismo y Cultura GADT', 'Turismo', null, null, 'Enlace con comunidades locales para el desarrollo turístico.', 'ACT-20', 'Subgobernación', 8],
            ['Fernando Javier Copa Soliz', 'Consultor en Gestión Ambiental', 'Medio Ambiente', '+591 79012345', 'f.copa@medioambiente.gob.bo', 'Encargado del monitoreo de cuencas y programas ambientales.', 'ACT-21', 'Servicio Departamental de Caminos (SEDECA)', 9],
            ['Patricia Lorena Luna Rojas', 'Directora de Planificación Institucional', 'Gestión Institucional', '+591 70123456', 'patricia.luna@gobernacion.gob.bo', 'Responsable del seguimiento a compromisos institucionales.', 'ACT-22', 'Gobierno Autónomo Departamental de Tarija (GADT)', 10],
            ['Samuel Isaac Ticona Herrera', 'Médico Jefe de Brigada Móvil', 'Salud', '+591 71122334', 's.ticona@salud-movil.bo', 'Lidera campañas de vacunación y atención primaria.', 'ACT-23', null, 11],
            ['Gilmar Miranda Zutara', 'Alcalde del Gobierno Autónomo Municipal de Padcaya', 'Gestión Institucional', '+591 72233445', 'b.rojas@institucion.gob.bo', 'Encargado de atender solicitudes de información.', 'ACT-24', 'Gobierno Autónomo Departamental de Tarija (GADT)', 12],
            ['SEDES', null, 'Salud', null, null, null, null, null, 6],
            ['SERNAP / Área Legal', null, 'Medio Ambiente', null, null, null, null, null, 10],
            ['Asamblea Legislativa Departamental', null, 'Gestión Institucional', null, null, null, null, null, 15],
            ['Rodrigo Ríos Calabi', null, null, null, null, null, null, null, null],
            ['Fernando Martínez Arnold', 'Director de Promoción Turística', 'Turismo', null, null, null, null, null, null]];
        foreach ($rows as [$nombre_completo,$cargo_rol,$area,$telefono,$email,$notas,$actualizaciones_registradas,$institucion,$accionOrden]) {
            $r = Responsable::updateOrCreate(['nombre_completo' => $nombre_completo], compact('cargo_rol', 'area', 'telefono', 'email', 'notas', 'actualizaciones_registradas', 'institucion'));
        }
    }
}
