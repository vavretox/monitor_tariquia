<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\InformeMensual;
use App\Notifications\MonthlyReportNotification;
use App\Support\MonthlyReportControl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformeMensualController extends Controller
{
    public function index(Request $request, MonthlyReportControl $control)
    {
        $request->validate([
            'periodo' => ['sometimes', 'required', 'date_format:Y-m'],
            'estado' => ['nullable', 'string', 'in:pendiente,sin_informe,presentado,correccion,aceptado'],
        ]);
        $period = $request->input('periodo', $control->currentPeriod());
        $control->validatePeriod($period);
        $canReview = $request->user()->hasAnyRole(['admin', 'tecnico']) && $request->user()->can('acciones.edit');
        $query = InformeMensual::with(['accion.demanda', 'accion.bitacoras.usuario.roles', 'accion.responsables', 'responsable', 'usuario', 'bitacora.usuario', 'revisor'])
            ->where('periodo', $period)->whereHas('accion')
            ->when(! $canReview, fn ($query) => $query->where('user_id', $request->user()->id));
        $all = $query->orderBy('id')->get();
        $all->each(fn ($record) => $control->recognizeExistingLog($record));
        $statuses = [
            'pendiente' => 'Pendiente', 'sin_informe' => 'Sin informe mensual',
            'presentado' => 'Presentado', 'correccion' => 'Requiere corrección', 'aceptado' => 'Aceptado',
        ];
        $status = $request->input('estado', '');
        abort_if($status !== '' && ! array_key_exists($status, $statuses), 422);
        $records = ($status ? $all->filter(fn ($record) => $record->estado_control === $status) : $all)
            ->sortBy([
                fn ($a, $b) => ($a->bitacora?->avance === null ? 1 : 0) <=> ($b->bitacora?->avance === null ? 1 : 0),
                fn ($a, $b) => ($b->bitacora?->avance ?? -1) <=> ($a->bitacora?->avance ?? -1),
                fn ($a, $b) => array_search($a->estado_control, ['sin_informe', 'correccion', 'pendiente', 'presentado', 'aceptado'])
                    <=> array_search($b->estado_control, ['sin_informe', 'correccion', 'pendiente', 'presentado', 'aceptado']),
                fn ($a, $b) => $a->id <=> $b->id,
            ])->values();
        $availableLogs = \App\Models\AccionBitacora::where('user_id', $request->user()->id)
            ->whereHas('accion')->whereYear('fecha_hora', substr($period, 0, 4))->whereMonth('fecha_hora', substr($period, 5, 2))
            ->whereNotNull('avance')->latest('id')->get()->groupBy('accion_ejecucion_id');

        return view('reportes.control_mensual', compact('period', 'records', 'all', 'statuses', 'canReview', 'availableLogs'));
    }

    public function submit(Request $request, InformeMensual $informe, MonthlyReportControl $control)
    {
        $action = AccionEjecucion::findOrFail($informe->accion_ejecucion_id);
        abort_unless((int) $informe->user_id === (int) $request->user()->id, 403);
        $data = $request->validate(['bitacora_id' => ['required', 'integer']]);
        $log = $action->bitacoras()->where('user_id', $request->user()->id)->findOrFail($data['bitacora_id']);
        $control->submit($informe, $log, $request->user()->id);

        return back()->with('success', 'Informe mensual presentado para revisión.');
    }

    public function review(Request $request, InformeMensual $informe, MonthlyReportControl $control)
    {
        abort_unless($request->user()->hasAnyRole(['admin', 'tecnico']) && $request->user()->can('acciones.edit'), 403);
        $action = AccionEjecucion::findOrFail($informe->accion_ejecucion_id);
        abort_if((int) $informe->user_id === (int) $request->user()->id, 403, 'No puede revisar su propio informe.');
        $data = $request->validate([
            'estado' => ['required', 'in:aceptado,correccion'],
            'comentario' => ['required_if:estado,correccion', 'nullable', 'string', 'max:5000'],
        ]);
        if ($data['estado'] === 'correccion' && trim($data['comentario'] ?? '') === '') {
            return back()->withErrors(['comentario' => 'Indique qué debe corregir el responsable.']);
        }
        DB::transaction(function () use ($informe, $data, $request, $action, $control) {
            $record = InformeMensual::whereKey($informe->id)->lockForUpdate()->firstOrFail();
            $control->recognizeExistingLog($record);
            abort_unless($record->estado_control === 'presentado' && $record->bitacora, 422, 'El informe debe estar presentado para revisarlo.');
            $record->update([
                'estado' => $data['estado'], 'revisor_id' => $request->user()->id,
                'revisado_at' => now(), 'comentario_revision' => $data['comentario'] ?? null,
            ]);
            $message = 'Informe de '.$record->periodo.' · '.$action->titulo.': '
                .($record->estado === 'aceptado' ? 'Aceptado.' : 'Requiere corrección. '.trim($data['comentario']));
            if ($record->estado === 'correccion') $action->observaciones()->create([
                'user_id' => $request->user()->id, 'mensaje' => $message,
            ]);
            activity()->performedOn($action)->causedBy($request->user())->event('updated')
                ->withProperties(['attributes' => ['informe_mensual' => $message]])->log('Informe mensual revisado');
            if ($record->usuario?->is_active) $record->usuario->notify(new MonthlyReportNotification($record, $message));
        });

        return back()->with('success', 'Revisión registrada y notificada al responsable con cuenta vinculada.');
    }
}
