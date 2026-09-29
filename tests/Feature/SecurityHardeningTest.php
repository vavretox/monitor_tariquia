<?php

namespace Tests\Feature;

use App\Models\Proyecto;
use App\Models\ProyectoArchivo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_evidence_requires_authentication_and_view_permission(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('proyectos/1/informe.pdf', 'contenido privado');

        $owner = User::factory()->create();
        $proyecto = Proyecto::create([
            'nombre' => 'Proyecto de prueba',
            'tipo' => 'salud',
            'estado' => 'planificado',
            'latitud' => -21.5,
            'longitud' => -64.7,
            'user_id' => $owner->id,
        ]);
        $archivo = ProyectoArchivo::create([
            'proyecto_id' => $proyecto->id,
            'ruta' => 'proyectos/1/informe.pdf',
            'nombre_original' => 'informe.pdf',
            'tipo_mime' => 'application/pdf',
            'tamano' => 17,
        ]);

        $this->get(route('evidencias.proyectos.descargar', $archivo))->assertRedirect(route('login'));

        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user)->get(route('evidencias.proyectos.descargar', $archivo))->assertForbidden();

        Permission::findOrCreate('proyectos.view', 'web');
        $user->givePermissionTo('proyectos.view');

        $this->actingAs($user)
            ->get(route('evidencias.proyectos.descargar', $archivo))
            ->assertOk()
            ->assertHeader('content-disposition');
    }

    public function test_deactivated_user_is_logged_out_on_the_next_request(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_api_requires_permission_and_validates_project_data(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        Sanctum::actingAs($user);

        $this->postJson(route('api.proyectos.store'), [])->assertForbidden();

        Permission::findOrCreate('proyectos.create', 'web');
        $user->givePermissionTo('proyectos.create');

        $this->postJson(route('api.proyectos.store'), [])->assertUnprocessable();

        $response = $this->postJson(route('api.proyectos.store'), [
            'nombre' => 'Puesto de salud',
            'tipo' => 'salud',
            'estado' => 'planificado',
            'latitud' => -21.53,
            'longitud' => -64.72,
            'user_id' => 999999,
        ])->assertCreated();

        $proyecto = Proyecto::findOrFail($response->json('id'));
        $this->assertSame($user->id, $proyecto->user_id);
    }
}