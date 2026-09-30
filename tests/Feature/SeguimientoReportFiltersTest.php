<?php

namespace Tests\Feature;

use App\Models\AccionEjecucion;
use App\Models\Demanda;
use App\Models\TipoNecesidad;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeguimientoReportFiltersTest extends TestCase
{
    use RefreshDatabase;

    public function test_matrix_filters_by_sector_and_search_text(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $user = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $agua = TipoNecesidad::firstOrCreate(['nombre' => 'Agua']);
        $salud = TipoNecesidad::firstOrCreate(['nombre' => 'Salud']);
        $demandaAgua = Demanda::create(['tipo_necesidad_id' => $agua->id, 'titulo' => 'Sistema de riego']);
        $demandaSalud = Demanda::create(['tipo_necesidad_id' => $salud->id, 'titulo' => 'Puesto mÃ©dico']);
        AccionEjecucion::create(['demanda_id' => $demandaAgua->id, 'titulo' => 'Inspeccionar tuberÃ­a']);
        AccionEjecucion::create(['demanda_id' => $demandaSalud->id, 'titulo' => 'Comprar insumos']);

        $response = $this->actingAs($user)->get(route('reportes.seguimiento', [
            'tipo_necesidad_id' => $agua->id,
            'buscar' => 'riego',
        ]));

        $response->assertOk()
            ->assertSee('Tipo o sector')
            ->assertSee('Sistema de riego')
            ->assertSee('Inspeccionar tuberÃ­a')
            ->assertDontSee('Puesto mÃ©dico');
    }
}
