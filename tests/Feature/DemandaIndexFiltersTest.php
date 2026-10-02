<?php

namespace Tests\Feature;

use App\Models\Demanda;
use App\Models\TipoNecesidad;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemandaIndexFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_demand_list_can_be_filtered_by_type(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $user = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $agua = TipoNecesidad::firstOrCreate(['nombre' => 'Agua']);
        $salud = TipoNecesidad::firstOrCreate(['nombre' => 'Salud']);
        Demanda::create(['tipo_necesidad_id' => $agua->id, 'titulo' => 'Sistema de riego']);
        Demanda::create(['tipo_necesidad_id' => $salud->id, 'titulo' => 'Puesto médico']);

        $response = $this->actingAs($user)->get(route('demandas.index', [
            'tipo_necesidad_id' => $agua->id,
        ]));

        $response->assertOk()
            ->assertSee('Todos los tipos')
            ->assertSee('Sistema de riego')
            ->assertDontSee('Puesto médico');
    }
}
