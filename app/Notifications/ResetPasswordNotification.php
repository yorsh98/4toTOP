<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->from(config('mail.notifications_from.address') ?: config('mail.from.address'), config('mail.notifications_from.name') ?: 'Notificaciones 4°TOP')
            ->subject('Restablecer contraseña – 4°TOP')
            ->view([
                'html' => 'emails.outlook.reset-password',
                'text' => 'emails.outlook.reset-password-text',
            ], [
                'url' => $this->resetUrl($notifiable),
                'userName' => $notifiable->name,
                'expireMinutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
            ]);
    }
}
