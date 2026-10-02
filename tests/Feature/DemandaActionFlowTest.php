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
            'estado' => 'pendiente', 'avance' => 0, 'comunidad_ids' => [$comunidad->id],
        ]);

        $accion = AccionEjecucion::firstOrFail();
        $response->assertRedirect(route('demandas.show', $demanda));
        $this->assertTrue($accion->demanda->is($demanda));
        $this->assertTrue($accion->comunidades->contains($comunidad));
    }

    public function test_action_only_accepts_communities_from_its_demand(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $user = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $incluida = Comunidad::create(['nombre' => 'Incluida', 'latitud' => -22, 'longitud' => -64]);
        $ajena = Comunidad::create(['nombre' => 'Ajena', 'latitud' => -22.1, 'longitud' => -64.1]);
        $demanda = Demanda::create(['titulo' => 'Demanda comunitaria']);
        $demanda->comunidades()->attach($incluida);

        $response = $this->actingAs($user)->post(route('acciones.store'), [
            'demanda_id' => $demanda->id,
            'comunidad_ids' => [$ajena->id],
            'titulo' => 'Acción inválida',
            'estado' => 'pendiente',
        ]);

        $response->assertSessionHasErrors('comunidad_ids.0');
        $this->assertDatabaseCount('acciones_ejecucion', 0);
    }
}
