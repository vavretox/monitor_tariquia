<?php

namespace App\Notifications;

use App\Models\AccionEjecucion;
use App\Models\AccionObservacion;
use Illuminate\Notifications\Notification;

class ActionObservationNotification extends Notification
{
    public function __construct(public AccionEjecucion $accion, public AccionObservacion $observacion) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'accion_id' => $this->accion->id,
            'titulo' => $this->accion->titulo,
            'mensaje' => $this->observacion->mensaje,
            'autor' => $this->observacion->usuario?->name ?? 'Sistema',
            'observacion_id' => $this->observacion->id,
            'respuesta' => $this->observacion->parent_id !== null,
        ];
    }
}
