<?php

namespace Tests\Feature;

use App\Models\AccionCompromiso;
use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\Responsable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommitmentActionHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_can_have_multiple_responsible_people_in_full_hierarchy(): void
    {
        $comunidad = Comunidad::create(['nombre' => 'Comunidad prueba', 'latitud' => -21, 'longitud' => -64]);
        $necesidad = Necesidad::create(['comunidad_id' => $comunidad->id, 'titulo' => 'Necesidad prueba', 'prioridad' => 'alta', 'estado' => 'identificada']);
        $compromiso = AccionCompromiso::create(['comunidad_id' => $comunidad->id, 'necesidad_id' => $necesidad->id, 'titulo' => 'Compromiso prueba', 'estado' => 'identificado']);
        $accion = AccionEjecucion::create(['accion_compromiso_id' => $compromiso->id, 'titulo' => 'Acción prueba']);
        $responsables = collect([
            Responsable::create(['nombre_completo' => 'Responsable uno']),
            Responsable::create(['nombre_completo' => 'Responsable dos']),
        ]);

        $accion->responsables()->sync($responsables->pluck('id'));

        $this->assertSame($comunidad->id, $accion->compromiso->necesidad->comunidad_id);
        $this->assertCount(2, $accion->fresh()->responsables);
        $this->assertTrue($responsables[0]->fresh()->accionesEjecucion->contains($accion));
    }
}
