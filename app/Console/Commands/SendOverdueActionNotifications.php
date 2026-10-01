<?php

namespace App\Console\Commands;

use App\Models\AccionEjecucion;
use App\Notifications\OverdueActionNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SendOverdueActionNotifications extends Command
{
    protected $signature = 'actions:notify-overdue {--date= : Fecha de evaluación YYYY-MM-DD}';

    protected $description = 'Envía alertas diarias a responsables con acciones vencidas no completadas';

    public function handle(): int
    {
        $today = $this->option('date') ? Carbon::parse($this->option('date'))->startOfDay() : today();
        $sent = 0;

        AccionEjecucion::withoutGlobalScopes()
            ->with(['responsables.user', 'demanda'])
            ->whereNotNull('fecha_limite')
            ->whereDate('fecha_limite', '<', $today)
            ->where('estado', '!=', 'completada')
            ->orderBy('id')
            ->chunkById(100, function ($acciones) use ($today, &$sent) {
                foreach ($acciones as $accion) {
                    $daysOverdue = (int) $accion->fecha_limite->diffInDays($today);
                    foreach ($accion->responsables as $responsable) {
                        $user = $responsable->user;
                        if (! $user || ! $user->is_active) {
                            continue;
                        }
                        $alreadySent = DB::table('overdue_action_notifications')
                            ->where('accion_ejecucion_id', $accion->id)
                            ->where('user_id', $user->id)
                            ->whereDate('notified_on', $today)->exists();
                        if ($alreadySent) {
                            continue;
                        }

                        $user->notify(new OverdueActionNotification($accion, $daysOverdue));
                        DB::table('overdue_action_notifications')->insert([
                            'accion_ejecucion_id' => $accion->id, 'user_id' => $user->id,
                            'notified_on' => $today->toDateString(), 'days_overdue' => $daysOverdue,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $sent++;
                    }
                }
            });

        $this->info("Notificaciones enviadas: {$sent}");

        return self::SUCCESS;
    }
}
