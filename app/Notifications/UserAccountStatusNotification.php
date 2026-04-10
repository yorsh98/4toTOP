<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserAccountStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $status,
        private readonly ?string $adminName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        [$subject, $line] = match ($this->status) {
            'approved' => [
                'Tu cuenta fue aprobada',
                'Tu cuenta ya fue aprobada y puedes iniciar sesión en el sistema.',
            ],
            'rejected' => [
                'Tu cuenta fue rechazada',
                'Tu solicitud de acceso fue rechazada por un administrador.',
            ],
            'suspended' => [
                'Tu cuenta fue suspendida',
                'Tu cuenta fue suspendida y no podrás ingresar hasta que sea reactivada.',
            ],
            'unsuspended' => [
                'Tu cuenta fue reactivada',
                'Tu cuenta fue reactivada y ya puedes volver a ingresar al sistema.',
            ],
            default => [
                'Actualización de cuenta',
                'Se actualizó el estado de tu cuenta en el sistema.',
            ],
        };

        $mail = (new MailMessage)
            ->from(
                config('mail.notifications_from.address', 'escaner2@pjud.cl'),
                config('mail.notifications_from.name', 'Notificaciones 4°TOP')
            )
            ->subject($subject)
            ->greeting('Hola '.$notifiable->name.',')
            ->line($line);

        if ($this->adminName) {
            $mail->line('Gestión realizada por: '.$this->adminName.'.');
        }

        return $mail->line('Si tienes dudas, contacta al administrador del sistema.');
    }
}
