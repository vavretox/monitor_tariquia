<?php

namespace Tests\Feature;

use App\Models\AccionBitacoraFoto;
use App\Models\AccionEjecucion;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\User;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EvidenceSecurityAndCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_responsible_cannot_open_photo_from_an_unassigned_action(): void
    {
        Storage::fake('local');
        $this->seed(RolesAndUsersSeeder::class);
        $owner = Responsable::create(['nombre_completo' => 'Responsable propietario']);
        $user = User::factory()->create();
        $user->assignRole('responsable');
        Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable ajeno']);
        $demanda = Demanda::create(['titulo' => 'Demanda', 'prioridad' => 'media', 'estado' => 'identificada']);
        $accion = AccionEjecucion::withoutGlobalScopes()->create(['demanda_id' => $demanda->id, 'titulo' => 'Acción privada', 'estado' => 'pendiente']);
        $accion->responsables()->attach($owner);
        $bitacora = $accion->bitacoras()->create(['fecha_hora' => now(), 'descripcion' => 'Trabajo privado']);
        $foto = $bitacora->fotos()->create(['ruta' => 'acciones/privada.jpg', 'nombre_original' => 'privada.jpg', 'tipo_mime' => 'image/jpeg']);
        Storage::disk('local')->put($foto->ruta, 'contenido');

        $this->actingAs($user)->get(route('evidencias.bitacoras.foto', $foto))->assertNotFound();
    }

    public function test_deleting_demand_removes_log_photos_from_storage(): void
    {
        Storage::fake('local');
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $demanda = Demanda::create(['titulo' => 'Demanda', 'prioridad' => 'alta', 'estado' => 'identificada']);
        $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Acción', 'estado' => 'pendiente']);
        $bitacora = $accion->bitacoras()->create(['fecha_hora' => now(), 'descripcion' => 'Trabajo']);
        $foto = AccionBitacoraFoto::create(['accion_bitacora_id' => $bitacora->id, 'ruta' => 'acciones/evidencia.jpg', 'nombre_original' => 'evidencia.jpg']);
        Storage::disk('local')->put($foto->ruta, 'contenido');

        $this->actingAs($admin)->delete(route('demandas.destroy', $demanda))->assertRedirect(route('demandas.index'));

        Storage::disk('local')->assertMissing($foto->ruta);
    }

    public function test_completed_action_with_log_photo_is_not_reported_without_evidence(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $demanda = Demanda::create(['titulo' => 'Demanda', 'prioridad' => 'alta', 'estado' => 'resuelta']);
        $accion = AccionEjecucion::create(['demanda_id' => $demanda->id, 'titulo' => 'Acción con evidencia', 'estado' => 'completada']);
        $bitacora = $accion->bitacoras()->create(['fecha_hora' => now(), 'descripcion' => 'Trabajo']);
        $bitacora->fotos()->create(['ruta' => 'acciones/evidencia.jpg', 'nombre_original' => 'evidencia.jpg']);

        $this->actingAs($admin)->get(route('reportes.alertas'))->assertOk()->assertDontSee('Acción con evidencia');
    }
}
