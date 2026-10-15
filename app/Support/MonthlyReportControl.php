<?php

namespace App\Support;

use App\Models\AccionBitacora;
use App\Models\AccionEjecucion;
use App\Models\InformeMensual;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MonthlyReportControl
{
    // Recognize existing work for display without rewriting logs or monthly records.
    // An unlinked person can only receive an administrator/technician's log when
    // they are the sole responsible for the action and the month's obligation.
    public function recognizeExistingLog(InformeMensual $record): void
    {
        if ($record->accion_bitacora_id || $record->estado !== 'pendiente'
            || $record->comentario_revision || $record->presentado_at) return;
        $record->loadMissing(['accion.bitacoras.usuario.roles', 'accion.responsables']);
        $action = $record->accion;
        if (! $action) return;
        $soleUnlinked = ! $record->user_id && $action->responsables->count() === 1
            && (int) $action->responsables->first()->id === (int) $record->responsable_id
            && ! $action->responsables->first()->user_id
            && InformeMensual::where('accion_ejecucion_id', $action->id)
                ->where('periodo', $record->periodo)->count() === 1;
        $log = $action->bitacoras->filter(function ($log) use ($record, $soleUnlinked) {
            $authorMatches = $record->user_id
                ? (int) $log->user_id === (int) $record->user_id
                : $soleUnlinked && $log->usuario?->hasAnyRole(['admin', 'tecnico']);
            return $authorMatches && $log->fecha_hora?->format('Y-m') === $record->periodo
                && ! $log->fecha_hora->isFuture() && $log->created_at && ! $log->created_at->isFuture()
                && trim($log->descripcion ?? '') !== '' && $log->avance !== null
                && $log->avance >= 0 && $log->avance <= 100;
        })->sortBy(fn ($log) => $log->created_at->format('Y-m-d H:i:s').sprintf('%020d', $log->id))->first();
        if (! $log) return;
        $record->fill([
            'accion_bitacora_id' => $log->id, 'estado' => 'presentado',
            'presentado_at' => $log->created_at, 'ultima_entrega_at' => $log->created_at,
        ])->setRelation('bitacora', $log);
    }

    public function currentPeriod(): string
    {
        return now(config('monthly_reports.timezone'))->format('Y-m');
    }

    public function validatePeriod(string $period): void
    {
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)
            || $period < config('monthly_reports.starts_at') || $period > $this->currentPeriod()) {
            throw ValidationException::withMessages(['periodo' => 'Seleccione un mes válido desde el inicio del control hasta el mes actual.']);
        }
    }

    // Only the current month's assignments create new obligations. Past records are preserved.
    public function synchronize(): int
    {
        $period = $this->currentPeriod();
        if ($period < config('monthly_reports.starts_at')) return 0;
        $end = now(config('monthly_reports.timezone'))->endOfMonth()->toDateString();
        $count = 0;
        AccionEjecucion::withoutGlobalScopes()->with('responsables')->where('estado', '!=', 'completada')
            ->where('created_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('fecha_inicio')->orWhereDate('fecha_inicio', '<=', $end))
            ->chunkById(100, function ($actions) use ($period, &$count) {
                foreach ($actions as $action) foreach ($action->responsables as $responsable) {
                    $record = InformeMensual::withoutEvents(fn () => InformeMensual::firstOrCreate([
                        'accion_ejecucion_id' => $action->id, 'responsable_id' => $responsable->id, 'periodo' => $period,
                    ], ['user_id' => $responsable->user_id]));
                    if ($record->wasRecentlyCreated) $count++;
                    if (! $record->user_id && $responsable->user_id) $record->update(['user_id' => $responsable->user_id]);
                }
            });

        return $count;
    }

    public function submit(InformeMensual $record, AccionBitacora $log, int $userId): void
    {
        $this->validatePeriod($record->periodo);
        if ((int) $record->user_id !== $userId || (int) $log->user_id !== $userId
            || (int) $record->accion_ejecucion_id !== (int) $log->accion_ejecucion_id
            || $log->fecha_hora->format('Y-m') !== $record->periodo
            || trim($log->descripcion) === '' || $log->avance === null || $log->avance < 0 || $log->avance > 100
            || $log->fecha_hora->isFuture()) {
            throw ValidationException::withMessages(['bitacora_id' => 'El informe requiere una bitácora propia de esta acción y del mes indicado, con descripción y porcentaje de avance, sin fecha futura.']);
        }
        DB::transaction(function () use ($record, $log, $userId) {
            $locked = InformeMensual::whereKey($record->id)->lockForUpdate()->firstOrFail();
            if ($locked->estado === 'aceptado') {
                throw ValidationException::withMessages(['bitacora_id' => 'Este informe ya fue aceptado.']);
            }
            if ($locked->estado === 'presentado' && (int) $locked->accion_bitacora_id === (int) $log->id) return;
            $locked->update([
                'accion_bitacora_id' => $log->id, 'estado' => 'presentado', 'presentado_at' => $locked->presentado_at ?? now(), 'ultima_entrega_at' => now(),
                'revisor_id' => null, 'revisado_at' => null, 'comentario_revision' => null,
            ]);
            activity()->performedOn($locked->accion)->causedBy($userId)->event('updated')
                ->withProperties(['attributes' => ['informe_mensual' => $locked->periodo.' · Presentado por '.$locked->responsable->nombre_completo]])
                ->log('Informe mensual presentado');
        });
    }

    public function registerLog(AccionBitacora $log): void
    {
        $period = $log->fecha_hora->format('Y-m');
        if ($period < config('monthly_reports.starts_at') || $period > $this->currentPeriod()) return;
        $this->synchronize();
        $record = InformeMensual::where('accion_ejecucion_id', $log->accion_ejecucion_id)
            ->where('user_id', $log->user_id)->where('periodo', $period)->first();
        if ($record && $record->estado !== 'aceptado') $this->submit($record, $log, (int) $log->user_id);
    }

    public function invalidateLog(AccionBitacora $log, bool $deleted = false): void
    {
        InformeMensual::where('accion_bitacora_id', $log->id)->get()->each(function ($record) use ($deleted) {
            $record->update([
                'estado' => $deleted ? 'pendiente' : 'correccion',
                'accion_bitacora_id' => $deleted ? null : $record->accion_bitacora_id,
                'comentario_revision' => $deleted
                    ? 'La bitácora fue eliminada. Debe presentar otro registro.'
                    : 'La bitácora cambió. Vuelva a presentar el informe para su revisión.',
            ]);
        });
    }
}
