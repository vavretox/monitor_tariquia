<?php

namespace App\Console\Commands;

use App\Models\InformeMensual;
use App\Models\User;
use App\Notifications\MonthlyReportNotification;
use App\Support\MonthlyReportControl;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ControlMonthlyReports extends Command
{
    protected $signature = 'actions:control-monthly {--dry-run : Solo mostrar pendientes, sin escribir ni notificar} {--no-notify : Generar obligaciones sin enviar notificaciones}';
    protected $description = 'Genera el control mensual y recuerda informes pendientes; avisa a revisores después del cierre';

    public function handle(MonthlyReportControl $control): int
    {
        $dry = (bool) $this->option('dry-run');
        if (! $dry) $this->info('Obligaciones nuevas: '.$control->synchronize());
        $today = now(config('monthly_reports.timezone'));
        $period = $control->currentPeriod();
        $reviewers = User::role(['admin', 'tecnico'])->where('is_active', true)->get()
            ->filter(fn ($user) => $user->can('acciones.edit'));
        $pending = 0;
        $sent = 0;
        InformeMensual::with(['accion.bitacoras.usuario.roles', 'accion.responsables', 'responsable', 'usuario'])->where('periodo', '<=', $period)
            ->whereHas('accion')->where(fn ($query) => $query->whereNull('accion_bitacora_id')->orWhere('estado', 'correccion'))
            ->chunkById(100, function ($records) use ($control, $today, $period, $reviewers, $dry, &$pending, &$sent) {
                foreach ($records as $record) {
                    $control->recognizeExistingLog($record);
                    if (! in_array($record->estado_control, ['pendiente', 'sin_informe', 'correccion'], true)) continue;
                    $pending++;
                    $late = $record->periodo < $period;
                    $remind = ! $late && $today->day >= $today->daysInMonth - config('monthly_reports.reminder_days') + 1;
                    if ($dry) {
                        $this->line($record->periodo.' · '.$record->accion->titulo.' · '.$record->responsable->nombre_completo.' · '.$record->estado_control);
                        continue;
                    }
                    if ($this->option('no-notify') || (! $late && ! $remind)) continue;
                    DB::transaction(function () use ($control, $record, $late, $reviewers, &$sent) {
                        $locked = InformeMensual::whereKey($record->id)->lockForUpdate()->firstOrFail();
                        $control->recognizeExistingLog($locked);
                        if ($locked->accion_bitacora_id && $locked->estado !== 'correccion') return;
                        $marker = $late ? 'alerta_at' : 'recordatorio_at';
                        if ($locked->{$marker}) return;
                        $message = ($late ? 'Informe mensual pendiente después del cierre' : 'Recordatorio de informe mensual')
                            .' · '.$record->periodo.' · '.$record->accion->titulo.' · '.$record->responsable->nombre_completo
                            .'. Registre una bitácora con descripción y porcentaje de avance; si no hubo avance, explique el motivo.';
                        $recipients = collect([$record->usuario])->filter(fn ($user) => $user && $user->is_active);
                        if ($late) $recipients = $recipients->merge($reviewers)->unique('id');
                        foreach ($recipients as $user) {
                            $user->notify(new MonthlyReportNotification($record, $message));
                            $sent++;
                        }
                        if ($recipients->isNotEmpty()) $locked->update([$marker => now()]);
                    });
                }
            });
        $this->info('Informes pendientes: '.$pending.'. Notificaciones enviadas: '.$sent.'.'.($dry ? ' Simulación sin cambios.' : ''));

        return self::SUCCESS;
    }
}
