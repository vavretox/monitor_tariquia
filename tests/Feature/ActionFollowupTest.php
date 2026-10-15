<?php

namespace Tests\Feature;

use App\Models\AccionEjecucion;
use App\Models\AccionObservacion;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\User;
use App\Notifications\ActionObservationNotification;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActionFollowupTest extends TestCase
{
    use RefreshDatabase;

    public function test_observation_and_reply_notify_the_correct_users(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('responsable');
        $responsable = Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable de prueba']);
        $action = AccionEjecucion::create(['demanda_id' => Demanda::create(['titulo' => 'Demanda'])->id, 'titulo' => 'Acción']);
        $action->responsables()->attach($responsable);

        $this->actingAs($admin)->post(route('acciones.observaciones.store', $action), ['mensaje' => 'Corregir informe'])
            ->assertRedirect(route('acciones.seguimiento', $action));
        $observation = AccionObservacion::firstOrFail();
        $this->assertSame(1, $user->unreadNotifications()->count());
        $this->assertSame(0, $admin->unreadNotifications()->count());

        $this->actingAs($user)->post(route('acciones.observaciones.store', $action), [
            'mensaje' => 'Informe corregido', 'parent_id' => $observation->id,
        ])->assertRedirect();
        $this->assertSame(1, $admin->unreadNotifications()->count());

        $this->actingAs($user)->getJson(route('acciones.seguimiento', $action))->assertOk()
            ->assertJsonFragment(['descripcion' => 'Corregir informe'])
            ->assertJsonFragment(['descripcion' => 'Informe corregido']);

        $this->actingAs($user)->post(route('acciones.observaciones.store', $action), ['mensaje' => 'Sin hilo'])
            ->assertForbidden();
        $this->actingAs($user)->post(route('acciones.observaciones.store', $action), ['mensaje' => 'Respuesta', 'parent_id' => 999999])
            ->assertNotFound();

        $notification = $user->notifications()->firstOrFail();
        $this->actingAs($admin)->post(route('profile.notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);
        $this->actingAs($user)->post(route('profile.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_responsable_cannot_read_or_comment_on_an_unassigned_action(): void
    {
        $this->seed(RolesAndUsersSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('responsable');
        $action = AccionEjecucion::create(['demanda_id' => Demanda::create(['titulo' => 'Otra demanda'])->id, 'titulo' => 'Otra acción']);
        $this->actingAs($user)->getJson(route('acciones.seguimiento', $action))->assertNotFound();
        $this->post(route('acciones.observaciones.store', $action), ['mensaje' => 'Mensaje'])->assertNotFound();
    }
}
