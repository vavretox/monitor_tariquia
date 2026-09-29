<?php

namespace Tests\Feature;

use App\Models\AccionCompromiso;
use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SeguimientoReportGroupingTest extends TestCase
{
    use RefreshDatabase;

    public function test_need_shared_by_communities_is_rendered_only_once(): void
    {
        $permission = Permission::create(['name' => 'dashboard.view']);
        $role = Role::create(['name' => 'reportes']);
        $role->givePermissionTo($permission);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);

        $primera = Comunidad::create(['nombre' => 'Acherales', 'latitud' => -22.01, 'longitud' => -64.31]);
        $segunda = Comunidad::create(['nombre' => 'Puesto Rueda', 'latitud' => -22.02, 'longitud' => -64.32]);
        $necesidad = Necesidad::create(['titulo' => 'Camino compartido']);
        $necesidad->comunidades()->attach([$primera->id, $segunda->id]);
        $compromiso = AccionCompromiso::create(['necesidad_id' => $necesidad->id, 'titulo' => 'Compromiso único']);
        AccionEjecucion::create(['accion_compromiso_id' => $compromiso->id, 'titulo' => 'Acción única']);

        $response = $this->actingAs($user)->get(route('reportes.seguimiento'));

        $response->assertOk()->assertSee('Acherales, Puesto Rueda')->assertSee('Camino compartido');
        $this->assertSame(1, substr_count($response->getContent(), 'Acción única'));
    }
}
