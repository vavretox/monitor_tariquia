<?php

namespace Tests\Feature;

use App\Models\AccionBitacora;
use App\Models\AccionEjecucion;
use App\Models\Demanda;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccionBitacoraTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_can_register_multiple_log_entries_with_photos(): void
    {
        Storage::fake('local');
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $demanda = Demanda::create(['titulo' => 'Mejorar acceso', 'prioridad' => 'alta', 'estado' => 'en_ejecucion']);
        $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Reparar camino', 'estado' => 'en_ejecucion', 'avance' => 10]);

        foreach ([35, 60] as $avance) {
            $response = $this->actingAs($admin)->post(route('acciones.bitacoras.store', $accion), [
                'fecha_hora' => now()->format('Y-m-d H:i:s'),
                'descripcion' => "Trabajo de campo hasta {$avance}%.",
                'avance' => $avance,
                'fotografias' => [UploadedFile::fake()->image("avance-{$avance}.jpg")],
            ]);
            $response->assertRedirect(route('acciones.show', $accion).'#bitacora');
        }

        $this->assertCount(2, $accion->bitacoras()->get());
        $this->assertSame(60, $accion->fresh()->avance);
        $foto = AccionBitacora::latest('id')->firstOrFail()->fotos()->firstOrFail();
        Storage::disk('local')->assertExists($foto->ruta);
        $this->actingAs($admin)->getJson(route('acciones.bitacoras.index', $accion))
            ->assertOk()
            ->assertJsonCount(2, 'registros')
            ->assertJsonPath('registros.1.fotos.0.nombre', 'avance-35.jpg');
    }

    public function test_log_description_and_date_are_required(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $demanda = Demanda::create(['titulo' => 'Demanda', 'prioridad' => 'media', 'estado' => 'identificada']);
        $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Acción', 'estado' => 'pendiente']);

        $this->actingAs($admin)->post(route('acciones.bitacoras.store', $accion), [])
            ->assertSessionHasErrors(['fecha_hora', 'descripcion']);
    }
    public function test_log_can_be_edited_without_losing_author_or_photos(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $demanda = Demanda::create(['titulo' => 'Demanda', 'prioridad' => 'media', 'estado' => 'identificada']);
        $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Acción', 'estado' => 'pendiente', 'avance' => 60]);
        $registro = $accion->bitacoras()->create(['user_id' => $admin->id, 'fecha_hora' => now(), 'descripcion' => 'Original', 'avance' => 10]);
        $foto = $registro->fotos()->create(['ruta' => 'evidencia.jpg', 'nombre_original' => 'evidencia.jpg']);
        $url = route('acciones.bitacoras.update', [$accion, $registro]);
        $data = ['fecha_hora' => '2026-10-02T09:30', 'descripcion' => 'Trabajo corregido', 'avance' => 10];

        $this->actingAs($admin)->get(route('acciones.show', $accion))->assertOk()->assertSee('Editar registro');
        $this->put($url, $data)->assertRedirect(route('acciones.show', $accion).'#bitacora');
        $this->assertSame('Trabajo corregido', $registro->fresh()->descripcion);
        $this->assertSame('2026-10-02 09:30', $registro->fresh()->fecha_hora->format('Y-m-d H:i'));
        $this->assertSame($admin->id, $registro->fresh()->user_id);
        $this->assertSame($foto->id, $registro->fotos()->sole()->id);
        $this->assertSame(60, $accion->fresh()->avance);

        $this->put($url, array_replace($data, ['avance' => 75]))->assertSessionHasNoErrors();
        $this->assertSame(75, $accion->fresh()->avance);
        $this->assertNotNull($accion->fresh()->ultima_actualizacion_avance);
        $this->put($url, ['fecha_hora' => '', 'descripcion' => '', 'avance' => 101])
            ->assertSessionHasErrors(['fecha_hora', 'descripcion', 'avance']);
        $this->assertSame(75, $registro->fresh()->avance);

        $otraAccion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Otra acción', 'estado' => 'pendiente']);
        $this->put(route('acciones.bitacoras.update', [$otraAccion, $registro]), $data)->assertNotFound();

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('acciones.view');
        $this->actingAs($viewer)->put($url, $data)->assertForbidden();
    }

}
