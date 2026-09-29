<?php

namespace Tests\Feature;

use App\Models\AccionCompromiso;
use App\Models\AccionEjecucion;
use App\Models\Responsable;
use App\Models\User;
use App\Notifications\OverdueActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OverdueActionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_daily_command_notifies_linked_responsible_once_with_days_overdue(): void
    {
        Notification::fake();
        $role = Role::where('name', 'responsable')->firstOrFail();
        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $responsable = Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable', 'email' => $user->email]);
        $compromiso = AccionCompromiso::withoutGlobalScopes()->create(['titulo' => 'Compromiso']);
        $accion = AccionEjecucion::withoutGlobalScopes()->create([
            'accion_compromiso_id' => $compromiso->id, 'titulo' => 'Acción vencida',
            'estado' => 'en_ejecucion', 'fecha_limite' => '2026-09-20',
        ]);
        $accion->responsables()->attach($responsable);

        $this->artisan('actions:notify-overdue', ['--date' => '2026-09-29'])->assertSuccessful();
        $this->artisan('actions:notify-overdue', ['--date' => '2026-09-29'])->assertSuccessful();

        Notification::assertSentToTimes($user, OverdueActionNotification::class, 1);
        Notification::assertSentTo($user, OverdueActionNotification::class, fn ($notification) => $notification->daysOverdue === 9);
        $this->assertDatabaseHas('overdue_action_notifications', [
            'accion_ejecucion_id' => $accion->id, 'user_id' => $user->id,
            'notified_on' => '2026-09-29', 'days_overdue' => 9,
        ]);
    }

    public function test_completed_action_is_not_notified(): void
    {
        Notification::fake();
        $user = User::factory()->create(['is_active' => true]);
        $responsable = Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable', 'email' => $user->email]);
        $compromiso = AccionCompromiso::withoutGlobalScopes()->create(['titulo' => 'Compromiso']);
        $accion = AccionEjecucion::withoutGlobalScopes()->create([
            'accion_compromiso_id' => $compromiso->id, 'titulo' => 'Terminada',
            'estado' => 'completada', 'fecha_limite' => '2026-09-20',
        ]);
        $accion->responsables()->attach($responsable);

        $this->artisan('actions:notify-overdue', ['--date' => '2026-09-29'])->assertSuccessful();

        Notification::assertNothingSent();
    }
}
