<?php

namespace Tests\Feature;

use App\Models\AccionEjecucion;
use App\Models\Comunidad;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsible_user_without_profile_cannot_see_actions(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $demanda = Demanda::create(['titulo' => 'Demanda protegida', 'prioridad' => 'alta', 'estado' => 'identificada']);
        AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Acción protegida', 'estado' => 'pendiente']);
        $user = User::factory()->create();
        $user->assignRole('responsable');

        $this->actingAs($user);

        $this->assertSame(0, AccionEjecucion::count());
    }

    public function test_deleting_responsible_deactivates_linked_user(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $responsable = Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable de prueba', 'cargo_rol' => 'Técnico', 'telefono' => '000', 'email' => 'responsable@example.test', 'institucion' => 'Prueba']);

        $this->actingAs($admin)->delete(route('responsables.destroy', $responsable))->assertRedirect();

        $this->assertDatabaseMissing('responsables', ['id' => $responsable->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'is_active' => false]);
    }

    public function test_map_detail_is_public_but_omits_internal_links_and_responsibles(): void
    {
        $comunidad = Comunidad::create(['nombre' => 'Comunidad prueba', 'latitud' => -22, 'longitud' => -64]);
        $demanda = Demanda::create(['titulo' => 'Demanda pública', 'prioridad' => 'media', 'estado' => 'identificada']);
        $demanda->comunidades()->attach($comunidad);
        $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Acción pública', 'estado' => 'pendiente']);
        $accion->comunidades()->attach($comunidad);

        $this->getJson(route('mapa.comunidades.show', $comunidad))
            ->assertOk()->assertJsonPath('url', null)->assertJsonPath('demandas.0.url', null)
            ->assertJsonCount(0, 'demandas.0.acciones.0.responsables');
    }

    public function test_map_only_includes_demands_and_actions_assigned_to_each_community(): void
    {
        $primera = Comunidad::create(['nombre' => 'Primera', 'latitud' => -22, 'longitud' => -64]);
        $segunda = Comunidad::create(['nombre' => 'Segunda', 'latitud' => -22.1, 'longitud' => -64.1]);
        $compartida = Demanda::create(['titulo' => 'Demanda compartida', 'prioridad' => 'media', 'estado' => 'identificada']);
        $compartida->comunidades()->attach([$primera->id, $segunda->id]);
        $exclusiva = Demanda::create(['titulo' => 'Solo segunda', 'prioridad' => 'alta', 'estado' => 'identificada']);
        $exclusiva->comunidades()->attach($segunda);
        $local = AccionEjecucion::create(['demanda_id' => $compartida->id, 'titulo' => 'Acción primera', 'estado' => 'pendiente']);
        $local->comunidades()->attach($primera);
        $ajena = AccionEjecucion::create(['demanda_id' => $compartida->id, 'titulo' => 'Acción segunda vencida', 'estado' => 'pendiente', 'fecha_limite' => today()->subDay()]);
        $ajena->comunidades()->attach($segunda);
        $ambas = AccionEjecucion::create(['demanda_id' => $compartida->id, 'titulo' => 'Acción compartida', 'estado' => 'completada']);
        $ambas->comunidades()->attach([$primera->id, $segunda->id]);

        $this->getJson(route('mapa.comunidades.show', $primera))->assertOk()
            ->assertJsonCount(1, 'demandas')->assertJsonPath('demandas.0.id', $compartida->id)
            ->assertJsonCount(2, 'demandas.0.acciones')
            ->assertJsonMissing(['titulo' => $ajena->titulo])
            ->assertJsonFragment(['titulo' => $local->titulo])
            ->assertJsonFragment(['titulo' => $ambas->titulo]);
        $this->getJson(route('mapa.comunidades.show', $segunda))->assertOk()
            ->assertJsonCount(2, 'demandas')->assertJsonMissing(['titulo' => $local->titulo])
            ->assertJsonFragment(['titulo' => $ajena->titulo])
            ->assertJsonFragment(['titulo' => $ambas->titulo]);
        $this->get('/')->assertOk()->assertViewHas('mapaComunidades', function ($comunidades) use ($primera, $segunda) {
            $local = $comunidades->firstWhere('id', $primera->id)['demandas'];
            $otra = $comunidades->firstWhere('id', $segunda->id)['demandas'];

            return $local->count() === 1 && $local->first()['acciones']->count() === 2
                && !$local->first()['acciones']->contains('vencida', true)
                && $otra->first()['acciones']->contains('vencida', true);
        });
    }

}
