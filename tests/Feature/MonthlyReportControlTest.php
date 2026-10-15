<?php

namespace Tests\Feature;

use App\Models\AccionEjecucion;
use App\Models\Demanda;
use App\Models\InformeMensual;
use App\Models\Responsable;
use App\Models\User;
use App\Support\MonthlyReportControl;
use Database\Seeders\RolesAndUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MonthlyReportControlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow(Carbon::parse('2026-10-12 15:00', 'America/Caracas'));
        $this->seed(RolesAndUsersSeeder::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fixture(): array
    {
        $admin = User::where('email', 'admin@tariquia.test')->firstOrFail();
        $user = User::factory()->create(['is_active' => true, 'must_change_password' => false]);
        $user->assignRole('responsable');
        $person = Responsable::create(['user_id' => $user->id, 'nombre_completo' => 'Responsable mensual']);
        $action = AccionEjecucion::create([
            'demanda_id' => Demanda::create(['titulo' => 'Demanda mensual'])->id,
            'titulo' => 'Trabajo mensual', 'estado' => 'en_ejecucion', 'avance' => 20,
        ]);
        $action->responsables()->attach($person);
        app(MonthlyReportControl::class)->synchronize();

        return [$admin, $user, $action, InformeMensual::where('accion_ejecucion_id', $action->id)->sole()];
    }

    public function test_every_responsable_has_an_independent_obligation_and_creation_is_idempotent(): void
    {
        [$admin, $user, $action] = $this->fixture();
        $other = Responsable::create(['nombre_completo' => 'Segundo responsable sin cuenta']);
        $action->responsables()->attach($other);
        app(MonthlyReportControl::class)->synchronize();
        app(MonthlyReportControl::class)->synchronize();
        $this->assertSame(2, InformeMensual::count());
        $this->assertSame(0, $user->notifications()->count());

        $this->actingAs($admin)->get(route('reportes.control-mensual'))->assertOk()
            ->assertSee('Segundo responsable sin cuenta')->assertSee('Sin cuenta vinculada');
        $this->actingAs($user)->get(route('reportes.control-mensual'))->assertOk()
            ->assertSee('Responsable mensual')->assertDontSee('Segundo responsable sin cuenta');
    }

    public function test_existing_log_for_sole_unlinked_person_is_visible_without_database_changes(): void
    {
        [$admin, , $action, $report] = $this->fixture();
        $report->responsable->update(['user_id' => null]);
        $report->update(['user_id' => null]);
        $log = $action->bitacoras()->create([
            'user_id' => $admin->id, 'fecha_hora' => '2026-10-08 10:00:00',
            'descripcion' => 'Entrega existente de Sara', 'avance' => 30,
            'created_at' => '2026-10-08 10:30:00',
        ]);
        $log->forceFill(['created_at' => '2026-10-08 10:30:00'])->save();
        $before = $report->fresh()->getAttributes();
        $logBefore = $log->fresh()->getAttributes();
        $this->actingAs($admin)->get(route('reportes.control-mensual', ['estado' => 'presentado']))
            ->assertOk()->assertSee('Entrega existente de Sara')->assertSee('30% de avance')
            ->assertSee('08/10/2026')->assertSee('Registrado por');
        $this->assertSame($before, $report->fresh()->getAttributes());
        $this->assertSame($logBefore, $log->fresh()->getAttributes());
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00', 'America/Caracas'));
        $this->artisan('actions:control-monthly', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame(0, $admin->notifications()->count());
        $this->artisan('actions:control-monthly')->assertExitCode(0);
        $this->assertSame(0, $admin->notifications()->count());
        $this->actingAs($admin)->post(route('informes-mensuales.revisar', $report), ['estado' => 'correccion', 'comentario' => 'Añadir detalle.'])
            ->assertSessionHasNoErrors();
        $this->assertSame('correccion', $report->fresh()->estado_control);
        $this->assertFalse($report->fresh()->fuera_de_plazo);
        $this->get(route('reportes.control-mensual', ['periodo' => '2026-10', 'estado' => 'correccion']))
            ->assertOk()->assertSee('Añadir detalle.');
        $this->assertSame($logBefore, $log->fresh()->getAttributes());
    }

    public function test_existing_own_log_is_detected_but_shared_admin_log_is_not_attributed(): void
    {
        [$admin, $user, $action, $report] = $this->fixture();
        $log = $action->bitacoras()->create([
            'user_id' => $user->id, 'fecha_hora' => '2026-10-08 10:00:00',
            'descripcion' => 'Informe anterior al control', 'avance' => 30,
        ]);
        $this->actingAs($admin)->get(route('reportes.control-mensual', ['estado' => 'presentado']))
            ->assertOk()->assertSee('Informe anterior al control');
        $this->assertNull($report->fresh()->accion_bitacora_id);
        $log->update(['user_id' => $admin->id]);
        $report->responsable->update(['user_id' => null]);
        $report->update(['user_id' => null]);
        $other = Responsable::create(['nombre_completo' => 'Otro responsable']);
        $action->responsables()->attach($other);
        app(MonthlyReportControl::class)->synchronize();
        $this->get(route('reportes.control-mensual', ['estado' => 'presentado']))
            ->assertOk()->assertDontSee('Informe anterior al control');
    }

    public function test_own_bitacora_presents_report_but_an_admin_log_does_not(): void
    {
        [$admin, $user, $action, $report] = $this->fixture();
        $data = ['fecha_hora' => '2026-10-12T10:00', 'descripcion' => 'Sin avance por lluvia; se requiere acceso.', 'avance' => 20];
        $this->actingAs($admin)->post(route('acciones.bitacoras.store', $action), $data)->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $report->fresh()->estado_control);
        $this->actingAs($user)->post(route('acciones.bitacoras.store', $action), $data)->assertSessionHasNoErrors();
        $this->assertSame('presentado', $report->fresh()->estado_control);
        $this->assertFalse($report->fresh()->fuera_de_plazo);
        $this->assertSame($user->id, $report->fresh()->bitacora->user_id);

        $this->actingAs($admin)->post(route('informes-mensuales.presentar', $report), [
            'bitacora_id' => $report->fresh()->accion_bitacora_id,
        ])->assertForbidden();
    }

    public function test_responsable_must_provide_description_and_progress_without_future_dates(): void
    {
        [, $user, $action, $report] = $this->fixture();
        $base = ['fecha_hora' => '2026-10-12T10:00', 'descripcion' => 'Trabajo realizado', 'avance' => 20];
        foreach ([
            array_replace($base, ['avance' => null]),
            array_replace($base, ['descripcion' => '  ']),
            array_replace($base, ['fecha_hora' => '2026-10-20T10:00']),
        ] as $data) {
            $this->actingAs($user)->post(route('acciones.bitacoras.store', $action), $data)->assertSessionHasErrors();
        }
        $this->assertSame(0, $action->bitacoras()->count());
        $this->assertSame('pendiente', $report->fresh()->estado_control);
    }

    public function test_revision_creates_observation_notifies_owner_and_resubmission_can_be_accepted(): void
    {
        [$admin, $user, $action, $report] = $this->fixture();
        $this->actingAs($user)->post(route('acciones.bitacoras.store', $action), [
            'fecha_hora' => '2026-10-12T10:00', 'descripcion' => 'Trabajo realizado', 'avance' => 30,
        ])->assertSessionHasNoErrors();
        $this->actingAs($user)->post(route('informes-mensuales.revisar', $report), ['estado' => 'aceptado'])->assertForbidden();
        $this->actingAs($admin)->post(route('informes-mensuales.revisar', $report), [
            'estado' => 'correccion', 'comentario' => '',
        ])->assertSessionHasErrors('comentario');
        $this->post(route('informes-mensuales.revisar', $report), [
            'estado' => 'correccion', 'comentario' => 'Detallar el resultado.',
        ])->assertSessionHasNoErrors();
        $this->assertSame('correccion', $report->fresh()->estado_control);
        $this->assertSame(1, $action->observaciones()->count());
        $this->assertSame(1, $user->unreadNotifications()->count());

        $log = $report->fresh()->bitacora;
        $this->actingAs($user)->put(route('acciones.bitacoras.update', [$action, $log]), [
            'fecha_hora' => '2026-10-12T10:00', 'descripcion' => 'Resultado corregido con detalle', 'avance' => 30,
        ])->assertSessionHasNoErrors();
        $this->post(route('informes-mensuales.presentar', $report), ['bitacora_id' => $log->id])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('informes-mensuales.revisar', $report), ['estado' => 'aceptado'])->assertSessionHasNoErrors();
        $this->assertSame('aceptado', $report->fresh()->estado_control);
        $this->assertSame(2, $user->unreadNotifications()->count());
        $this->assertSame($admin->id, $report->fresh()->revisor_id);
    }

    public function test_backdating_a_late_delivery_does_not_make_it_on_time_and_first_receipt_is_preserved(): void
    {
        [, $user, $action, $report] = $this->fixture();
        Carbon::setTestNow(Carbon::parse('2026-11-02 10:00', 'America/Caracas'));
        $this->actingAs($user)->post(route('acciones.bitacoras.store', $action), [
            'fecha_hora' => '2026-10-25T10:00', 'descripcion' => 'Trabajo del mes anterior presentado tarde', 'avance' => 40,
        ])->assertSessionHasNoErrors();
        $this->assertTrue($report->fresh()->fuera_de_plazo);
        $first = $report->fresh()->presentado_at->toDateTimeString();
        $log = $report->fresh()->bitacora;
        $log->update(['descripcion' => 'Ajuste al informe']);
        app(MonthlyReportControl::class)->invalidateLog($log);
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00', 'America/Caracas'));
        $this->post(route('informes-mensuales.presentar', $report), ['bitacora_id' => $log->id])->assertSessionHasNoErrors();
        $this->assertSame($first, $report->fresh()->presentado_at->toDateTimeString());
        $this->assertNotSame($first, $report->fresh()->ultima_entrega_at->toDateTimeString());
    }

    public function test_monthly_reminder_and_overdue_alert_are_sent_once_and_dry_run_writes_nothing(): void
    {
        [$admin, $user, , $report] = $this->fixture();
        Carbon::setTestNow(Carbon::parse('2026-10-28 10:00', 'America/Caracas'));
        $this->artisan('actions:control-monthly', ['--dry-run' => true])->assertExitCode(0);
        $this->assertSame(0, $user->notifications()->count());
        $this->assertNull($report->fresh()->recordatorio_at);
        $this->artisan('actions:control-monthly')->assertExitCode(0);
        $this->artisan('actions:control-monthly')->assertExitCode(0);
        $this->assertSame(1, $user->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());

        Carbon::setTestNow(Carbon::parse('2026-11-01 10:00', 'America/Caracas'));
        $this->artisan('actions:control-monthly')->assertExitCode(0);
        $this->artisan('actions:control-monthly')->assertExitCode(0);
        $this->assertSame(2, $user->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertNotNull($report->fresh()->alerta_at);
        $this->assertSame('sin_informe', $report->fresh()->estado_control);
    }

    public function test_completed_actions_do_not_create_next_months_obligations(): void
    {
        [, , $action, $report] = $this->fixture();
        $action->update(['estado' => 'completada']);
        Carbon::setTestNow(Carbon::parse('2026-11-01 10:00', 'America/Caracas'));
        app(MonthlyReportControl::class)->synchronize();
        $this->assertSame(1, InformeMensual::count());
        $this->assertSame('sin_informe', $report->fresh()->estado_control);
    }

    public function test_invalid_periods_and_other_users_logs_cannot_be_used(): void
    {
        [$admin, $user, $action, $report] = $this->fixture();
        $otherLog = $action->bitacoras()->create([
            'user_id' => $admin->id, 'fecha_hora' => '2026-10-12 10:00:00',
            'descripcion' => 'Registro de otro autor', 'avance' => 20,
        ]);
        $this->actingAs($user)->post(route('informes-mensuales.presentar', $report), [
            'bitacora_id' => $otherLog->id,
        ])->assertNotFound();
        foreach (['2026-09', '2026-11', '2026-13', ['invalid']] as $period) {
            $this->getJson(route('reportes.control-mensual', ['periodo' => $period]))->assertUnprocessable();
        }
        $this->assertSame('pendiente', $report->fresh()->estado_control);
    }

    public function test_editing_or_deleting_report_log_invalidates_acceptance(): void
    {
        [$admin, $user, $action, $report] = $this->fixture();
        $this->actingAs($user)->post(route('acciones.bitacoras.store', $action), [
            'fecha_hora' => '2026-10-12T10:00', 'descripcion' => 'Trabajo', 'avance' => 25,
        ]);
        $this->actingAs($admin)->post(route('informes-mensuales.revisar', $report), ['estado' => 'aceptado']);
        $log = $report->fresh()->bitacora;
        $this->actingAs($user)->put(route('acciones.bitacoras.update', [$action, $log]), [
            'fecha_hora' => '2026-10-12T10:00', 'descripcion' => 'Cambio después de aprobar', 'avance' => 30,
        ])->assertSessionHasNoErrors();
        $this->assertSame('correccion', $report->fresh()->estado_control);
        $this->delete(route('acciones.bitacoras.destroy', [$action, $log]))->assertSessionHasNoErrors();
        $this->assertSame('pendiente', $report->fresh()->estado_control);
        $this->assertNull($report->fresh()->accion_bitacora_id);
    }
}
