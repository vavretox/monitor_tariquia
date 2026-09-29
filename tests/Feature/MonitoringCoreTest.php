<?php

namespace Tests\Feature;

use App\Models\AccionCompromiso;
use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MonitoringCoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_progress_creates_a_chronological_history_entry(): void
    {
        $user = $this->admin();
        [$compromiso, $accion] = $this->trackingChain();

        $response = $this->actingAs($user)->put(route('acciones.update', $accion), [
            'accion_compromiso_id' => $compromiso->id,
            'titulo' => $accion->titulo,
            'estado' => 'en_ejecucion',
            'avance' => 45,
            'comentario_avance' => 'Se concluyó el levantamiento técnico.',
        ]);

        $response->assertRedirect(route('compromisos.show', $compromiso));
        $this->assertDatabaseHas('historial_avances', [
            'accion_ejecucion_id' => $accion->id,
            'user_id' => $user->id,
            'avance' => 45,
            'estado' => 'en_ejecucion',
            'comentario' => 'Se concluyó el levantamiento técnico.',
        ]);
        $this->assertNotNull($accion->fresh()->ultima_actualizacion_avance);
    }

    public function test_deleting_primary_community_does_not_delete_a_shared_need(): void
    {
        $first = Comunidad::create(['nombre' => 'Primera', 'latitud' => -21, 'longitud' => -64]);
        $second = Comunidad::create(['nombre' => 'Segunda', 'latitud' => -22, 'longitud' => -65]);
        $need = Necesidad::create(['comunidad_id' => $first->id, 'titulo' => 'Necesidad compartida']);
        $need->comunidades()->sync([$first->id, $second->id]);

        $first->delete();

        $this->assertDatabaseHas('necesidades', ['id' => $need->id, 'comunidad_id' => null]);
        $this->assertTrue($need->fresh()->comunidades->contains($second));
    }

    private function admin(): User
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        return $user;
    }

    private function trackingChain(): array
    {
        $community = Comunidad::create(['nombre' => 'Comunidad monitoreo', 'latitud' => -21, 'longitud' => -64]);
        $need = Necesidad::create(['comunidad_id' => $community->id, 'titulo' => 'Necesidad monitoreo']);
        $need->comunidades()->attach($community);
        $commitment = AccionCompromiso::create(['comunidad_id' => $community->id, 'necesidad_id' => $need->id, 'titulo' => 'Compromiso monitoreo', 'estado' => 'en_ejecucion']);
        $action = AccionEjecucion::create(['accion_compromiso_id' => $commitment->id, 'titulo' => 'Acción monitoreo', 'estado' => 'pendiente', 'avance' => 0]);
        return [$commitment, $action];
    }
}
