<?php

namespace App\Notifications;

use App\Models\InformeMensual;
use Illuminate\Notifications\Notification;

class MonthlyReportNotification extends Notification
{
    public function __construct(public InformeMensual $informe, public string $mensaje) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'accion_id' => $this->informe->accion_ejecucion_id,
            'titulo' => 'Control mensual · '.$this->informe->periodo,
            'mensaje' => $this->mensaje,
            'autor' => 'Control mensual',
            'periodo' => $this->informe->periodo,
            'informe_id' => $this->informe->id,
        ];
    }
}
