<?php

namespace Tests\Feature;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaActionFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_demanda_can_generate_an_action(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $user = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $comunidad = Comunidad::create(['nombre' => 'Prueba', 'latitud' => -22, 'longitud' => -64]);

        $response = $this->actingAs($user)->post(route('demandas.store'), [
            'comunidad_ids' => [$comunidad->id], 'titulo' => 'Agua potable',
            'prioridad' => 'alta', 'estado' => 'identificada',
        ]);

        $demanda = Demanda::firstOrFail();
        $response->assertRedirect(route('demandas.show', $demanda));

        $response = $this->actingAs($user)->post(route('acciones.store'), [
            'demanda_id' => $demanda->id, 'titulo' => 'Realizar inspección',
            'estado' => 'pendiente', 'avance' => 0,
        ]);

        $accion = AccionEjecucion::firstOrFail();
        $response->assertRedirect(route('demandas.show', $demanda));
        $this->assertTrue($accion->demanda->is($demanda));
    }
}
