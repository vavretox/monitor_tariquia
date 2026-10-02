<?php

namespace Tests\Feature;

use App\Models\AccionEjecucion;
use App\Models\Demanda;
use App\Models\Responsable;
use App\Models\User;
use App\Notifications\OverdueActionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OverdueActionNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_sent_daily_until_action_state_changes(): void
    {
        Notification::fake();
        [$accion, $user] = $this->overdueAction();

        $this->artisan('actions:notify-overdue', ['--date' => '2026-10-01'])->assertSuccessful();
        $this->artisan('actions:notify-overdue', ['--date' => '2026-10-02'])->assertSuccessful();
        Notification::assertSentToTimes($user, OverdueActionNotification::class, 2);

        $accion->update(['estado' => 'en_ejecucion']);
        $this->artisan('actions:notify-overdue', ['--date' => '2026-10-03'])->assertSuccessful();
        Notification::assertSentToTimes($user, OverdueActionNotification::class, 2);
    }

    public function test_new_log_stops_follow_up_alerts(): void
    {
        Notification::fake();
        [$accion, $user] = $this->overdueAction();

        $this->artisan('actions:notify-overdue', ['--date' => '2026-10-01'])->assertSuccessful();
        $accion->bitacoras()->create([
            'fecha_hora' => now(),
            'descripcion' => 'Se registró el seguimiento de la acción.',
        ]);
        $this->artisan('actions:notify-overdue', ['--date' => '2026-10-02'])->assertSuccessful();

        Notification::assertSentToTimes($user, OverdueActionNotification::class, 1);
    }

    public function test_overdue_notification_uses_alert_mailer_and_sender(): void
    {
        [$accion, $user] = $this->overdueAction();
        config()->set('mail.alerts_from', ['address' => 'alertas@example.test', 'name' => 'Alertas Tariquía']);

        $message = (new OverdueActionNotification($accion, 3))->toMail($user);

        $this->assertSame('alerts', $message->mailer);
        $this->assertSame(['alertas@example.test', 'Alertas Tariquía'], $message->from);
    }

    private function overdueAction(): array
    {
        $user = User::factory()->create(['is_active' => true]);
        $responsable = Responsable::create([
            'user_id' => $user->id,
            'nombre_completo' => 'Responsable de prueba',
        ]);
        $demanda = Demanda::create(['titulo' => 'Demanda vencida']);
        $accion = AccionEjecucion::create([
            'demanda_id' => $demanda->id,
            'titulo' => 'Acción vencida',
            'estado' => 'pendiente',
            'fecha_limite' => '2026-09-20',
        ]);
        $accion->responsables()->attach($responsable);

        return [$accion, $user];
    }
}
