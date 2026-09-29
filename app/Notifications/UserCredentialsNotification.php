<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly string $temporaryPassword) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Acceso a '.config('app.name'))
            ->greeting('Hola, '.$notifiable->name)
            ->line('Se creó una cuenta institucional para ti.')
            ->line('Usuario: '.$notifiable->email)
            ->line('Contraseña temporal: '.$this->temporaryPassword)
            ->action('Ingresar al sistema', route('login'))
            ->line('Por seguridad, cambia esta contraseña al ingresar por primera vez.')
            ->line('Si no esperabas este acceso, informa al administrador.')
            ->salutation("Ing. Horacio Daniel Poveda Martínez  \nAdministrador de Infraestructura y Telecomunicaciones  \nhoracio.poveda@tarija.gob.bo - 69317339");
    }
}
