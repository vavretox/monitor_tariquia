<?php

namespace App\Http\Controllers;

use App\Models\AccionEjecucion;
use App\Models\AccionObservacion;
use App\Models\User;
use App\Notifications\ActionObservationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class AccionSeguimientoController extends Controller
{
    public function show(Request $request, AccionEjecucion $accione)
    {
        $accione->load(['responsables.user', 'bitacoras.usuario', 'observaciones.usuario']);
        $events = collect();
        $activities = Activity::forSubject($accione)->with('causer')->orderBy('created_at')->orderBy('id')->get();
        $created = $activities->firstWhere('event', 'created');
        $events->push([
            'fecha' => $accione->created_at, 'titulo' => 'Acción creada',
            'usuario' => $created?->causer?->name ?? 'Sistema',
            'descripcion' => 'Se registró la acción en el sistema.', 'tipo' => 'creacion', 'avance' => null,
        ]);
        foreach ($activities->where('event', 'updated') as $activity) {
            $changes = [];
            $old = $activity->properties->get('old', []);
            foreach ($activity->properties->get('attributes', []) as $field => $value) {
                if (in_array($field, ['updated_at', 'ultima_actualizacion_avance'])) continue;
                $format = fn ($v) => $v === null ? 'Sin definir' : (is_scalar($v) ? str_replace('_', ' ', (string) $v) : json_encode($v));
                $changes[] = ucfirst(str_replace('_', ' ', $field)).': '.$format($old[$field] ?? null).' → '.$format($value);
            }
            if ($changes) $events->push([
                'fecha' => $activity->created_at, 'titulo' => 'Acción actualizada',
                'usuario' => $activity->causer?->name ?? 'Sistema',
                'descripcion' => implode("
", $changes), 'tipo' => 'cambio', 'avance' => null,
            ]);
        }
        foreach (\App\Models\HistorialAvance::with('usuario')->where('accion_ejecucion_id', $accione->id)->get() as $history) {
            $events->push([
                'fecha' => $history->created_at, 'titulo' => 'Avance registrado',
                'usuario' => $history->usuario?->name ?? 'Sistema',
                'descripcion' => ucfirst(str_replace('_', ' ', $history->estado)).($history->comentario ? "\n".$history->comentario : ''),
                'tipo' => 'cambio', 'avance' => $history->avance,
            ]);
        }
        foreach ($accione->bitacoras as $log) $events->push([
            'fecha' => $log->fecha_hora, 'titulo' => 'Trabajo registrado',
            'usuario' => $log->usuario?->name ?? 'Usuario eliminado',
            'descripcion' => $log->descripcion, 'tipo' => 'bitacora', 'avance' => $log->avance,
        ]);
        foreach ($accione->observaciones as $observation) $events->push([
            'fecha' => $observation->created_at, 'titulo' => $observation->parent_id ? 'Respuesta a observación #'.$observation->parent_id : 'Observación',
            'usuario' => $observation->usuario?->name ?? 'Usuario eliminado',
            'descripcion' => $observation->mensaje, 'tipo' => 'observacion', 'avance' => null,
            'observacion_id' => $observation->id,
        ]);
        $events = $events->sortByDesc('fecha')->values();
        if ($request->expectsJson()) return response()->json([
            'titulo' => $accione->titulo, 'estado' => $accione->estado,
            'avance' => $accione->avance_seguimiento,
            'responsables' => $accione->responsables->pluck('nombre_completo')->join(', '),
            'url' => route('acciones.seguimiento', $accione),
            'eventos' => $events->map(fn ($event) => array_merge($event, ['fecha' => $event['fecha']?->format('d/m/Y · H:i') ?? 'Sin fecha'])),
        ]);

        return view('acciones_ejecucion.seguimiento', ['accion' => $accione, 'eventos' => $events->whereIn('tipo', ['bitacora', 'observacion'])->values()]);
    }

    public function store(Request $request, AccionEjecucion $accione)
    {
        $data = $request->validate([
            'mensaje' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
        ]);
        $parent = isset($data['parent_id'])
            ? $accione->observaciones()->findOrFail($data['parent_id']) : null;
        $user = $request->user();
        abort_if($user->hasRole('responsable') && ! $parent, 403);
        abort_if(trim($data['mensaje']) === '', 422);
        $accione->load('responsables.user');
        $recipients = $accione->responsables->pluck('user')->filter();
        if ($parent?->user_id) {
            $author = User::find($parent->user_id);
            if ($author) $recipients->push($author);
        }
        $recipients = $recipients->unique('id')->filter(fn ($recipient) => $recipient->id !== $user->id && $recipient->is_active);
        DB::transaction(function () use ($accione, $data, $user, $recipients) {
            $observation = $accione->observaciones()->create([
                'user_id' => $user->id, 'mensaje' => trim($data['mensaje']),
                'parent_id' => $data['parent_id'] ?? null,
            ]);
            $observation->setRelation('usuario', $user);
            foreach ($recipients as $recipient) $recipient->notify(new ActionObservationNotification($accione, $observation));
        });

        return redirect()->route('acciones.seguimiento', $accione)->with('success',
            'Mensaje registrado. Se notificó a '.$recipients->count().' usuario(s) con cuenta vinculada.');
    }
}
