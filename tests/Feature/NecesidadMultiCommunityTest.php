<?php

namespace Tests\Feature;

use App\Models\Comunidad;
use App\Models\Necesidad;
use App\Models\TipoNecesidad;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NecesidadMultiCommunityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_need_can_be_created_for_multiple_communities(): void
    {
        $role = Role::create(['name' => 'admin']);
        $user = User::factory()->create(['email_verified_at' => now()]);
        $user->assignRole($role);
        $communities = collect([
            Comunidad::create(['nombre' => 'Comunidad uno', 'latitud' => -21, 'longitud' => -64]),
            Comunidad::create(['nombre' => 'Comunidad dos', 'latitud' => -22, 'longitud' => -65]),
        ]);

        $tipo = TipoNecesidad::where('nombre', 'Agua')->firstOrFail();

        $response = $this->actingAs($user)->post(route('necesidades.store'), [
            'tipo_necesidad_id' => $tipo->id,
            'titulo' => 'Acceso compartido al agua',
            'descripcion' => 'Necesidad que involucra a dos comunidades.',
            'prioridad' => 'alta',
            'estado' => 'identificada',
            'comunidad_ids' => $communities->pluck('id')->all(),
        ]);

        $necesidad = Necesidad::where('titulo', 'Acceso compartido al agua')->firstOrFail();
        $response->assertRedirect(route('necesidades.show', $necesidad));
        $this->assertEqualsCanonicalizing($communities->pluck('id')->all(), $necesidad->comunidades->pluck('id')->all());
        $this->assertSame($communities->first()->id, $necesidad->comunidad_id);
        $this->assertSame($tipo->id, $necesidad->tipo_necesidad_id);
    }
}
