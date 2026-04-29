<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BitacoraPruebasNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $senderName,
        private readonly string $documentosUrl,
        private readonly string $bitacoraPublicUrl,
        private readonly ?string $rit = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(
                config('mail.notifications_from.address', 'escaner2@pjud.cl'),
                config('mail.notifications_from.name', 'Notificaciones 4°TOP')
            )
            ->subject('Notificacion de Bitacora de Pruebas')
            ->view('emails.v2.bitacora-pruebas-outlook', [
                'subject' => 'Notificacion de Bitacora de Pruebas',
                'senderName' => $this->senderName,
                'rit' => $this->rit,
                'documentosUrl' => $this->documentosUrl,
                'bitacoraPublicUrl' => $this->bitacoraPublicUrl,
                'recipientName' => data_get($notifiable, 'name', 'Usuario'),
            ]);
    }
}
