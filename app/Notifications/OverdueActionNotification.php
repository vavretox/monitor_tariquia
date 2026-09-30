<?php

namespace App\Notifications;

use App\Models\AccionEjecucion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OverdueActionNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly AccionEjecucion $accion, public readonly int $daysOverdue) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $responsables = $this->accion->responsables->pluck('nombre_completo')->join(', ');

        return (new MailMessage)
            ->subject("Acción vencida: {$this->accion->titulo}")
            ->greeting("Hola, {$notifiable->name}")
            ->line('La siguiente acción continúa sin estado Completada después de su fecha límite.')
            ->line("Acción: {$this->accion->titulo}")
            ->line('Demanda: '.($this->accion->demanda?->titulo ?: 'Sin demanda'))
            ->line('Fecha límite: '.$this->accion->fecha_limite->format('d/m/Y'))
            ->line("Días de retraso: {$this->daysOverdue}")
            ->line('Estado actual: '.ucfirst(str_replace('_', ' ', $this->accion->estado)))
            ->line('Responsables: '.($responsables ?: 'Sin responsables registrados'))
            ->action('Actualizar acción', route('acciones.edit', $this->accion))
            ->line('Actualiza el estado y registra el seguimiento correspondiente.');
    }
}
