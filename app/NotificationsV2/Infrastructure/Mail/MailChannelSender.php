<?php

namespace App\NotificationsV2\Infrastructure\Mail;

use App\Exports\ProgramacionDiariaExport;
use App\Mail\ProgramacionDiariaHtmlMail;
use App\NotificationsV2\Application\Contracts\ChannelSender;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class MailChannelSender implements ChannelSender
{
    public function send(NotificationDispatch $dispatch, array $renderedContent): void
    {
        $mailer = (string) config('notifications_v2.mailer', config('mail.default'));
        $from = config('mail.notifications_from', []);

        $subject = (string) ($renderedContent['subject'] ?? 'Notificacion');
        $html = (string) ($renderedContent['html'] ?? '');

        $dispatch->loadMissing('recipients');

        if ($dispatch->payload['birthday_mail'] ?? false) {
            foreach ($dispatch->recipients as $recipient) {
                if ($recipient->status === 'sent') {
                    continue;
                }
                try {
                    Mail::mailer($mailer)->send([], [], function (Message $message) use ($recipient, $subject, $html, $dispatch): void {
                        $filename = str_contains($dispatch->event_key, 'saludo') ? 'cumpleanos.png' : 'Cumplere.png';
                        $image = $message->embed(public_path('img/cumpleanos/'.$filename));
                        $body = str_replace(asset('img/cumpleanos/'.$filename), $image, $html);
                        $message->to($recipient->email, $recipient->name ?: null)->subject($subject)
                            ->from(config('mail.from.address'), '4º Tribunal de Juicio Oral en lo Penal de Santiago')->html($body);
                    });
                    $recipient->update(['status' => 'sent', 'failure_reason' => null, 'sent_at' => now()]);
                } catch (Throwable $exception) {
                    $recipient->update(['status' => 'failed', 'failure_reason' => $this->failureReason($exception)]);
                    Log::warning('No se pudo enviar un correo de cumpleaños.', ['dispatch_id' => $dispatch->id]);
                }
            }

            return;
        }

        $programacion = $dispatch->payload['programacion_diaria'] ?? null;
        $excel = is_array($programacion)
            ? (new ProgramacionDiariaExport(
                $programacion['fecha'],
                $programacion['export_snapshot']['jueces_ausentes'] ?? null,
                $programacion['export_snapshot'] ?? null,
            ))->raw()
            : null;

        if (is_array($programacion)) {
            try {
                $this->sendProgramacion(
                    'notifica_4top_santiago@pjud.cl',
                    $dispatch->recipients->pluck('email')->all(),
                    $programacion,
                    $excel,
                );

                foreach ($dispatch->recipients as $recipient) {
                    $recipient->update([
                        'status' => 'sent',
                        'failure_reason' => null,
                        'sent_at' => now(),
                    ]);

                    Log::info('notifications_v2.mail.recipient_sent', [
                        'dispatch_id' => $dispatch->id,
                        'event_key' => $dispatch->event_key,
                        'recipient_email' => $recipient->email,
                        'recipient_type' => $recipient->type,
                        'status' => 'sent',
                    ]);
                }

                return;
            } catch (Throwable $exception) {
                foreach ($dispatch->recipients as $recipient) {
                    $recipient->update([
                        'status' => 'failed',
                        'failure_reason' => $this->failureReason($exception),
                    ]);

                    Log::warning('notifications_v2.mail.recipient_failed', [
                        'dispatch_id' => $dispatch->id,
                        'event_key' => $dispatch->event_key,
                        'recipient_email' => $recipient->email,
                        'recipient_type' => $recipient->type,
                        'status' => 'failed',
                        'error_code' => $exception->getCode(),
                        'error' => $exception->getMessage(),
                    ]);
                }

                return;
            }
        }

        $text = $renderedContent['text'] ?? '';

        foreach ($dispatch->recipients as $recipient) {
            if ($recipient->status === 'sent') {
                continue;
            }

            try {
                Mail::mailer($mailer)->send([], [], function (Message $message) use ($recipient, $from, $subject, $html, $text): void {
                    $message->to($recipient->email, $recipient->name ?: null);
                    $message->subject($subject);

                    if (! empty($from['address'])) {
                        $message->from((string) $from['address'], (string) ($from['name'] ?? 'Notificaciones'));
                    }

                    $message->html($html);
                    $message->text($text);
                });

                $recipient->update([
                    'status' => 'sent',
                    'failure_reason' => null,
                    'sent_at' => now(),
                ]);

                Log::info('notifications_v2.mail.recipient_sent', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $dispatch->event_key,
                    'recipient_email' => $recipient->email,
                    'recipient_type' => $recipient->type,
                    'status' => 'sent',
                ]);

                Log::channel('mail_audit')->info('notifications_v2.mail.recipient_sent', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $dispatch->event_key,
                    'mailer' => $mailer,
                    'recipient_email' => $recipient->email,
                    'recipient_type' => $recipient->type,
                    'status' => 'sent',
                ]);
            } catch (Throwable $exception) {
                $recipient->update([
                    'status' => 'failed',
                    'failure_reason' => $this->failureReason($exception),
                ]);

                Log::warning('notifications_v2.mail.recipient_failed', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $dispatch->event_key,
                    'recipient_email' => $recipient->email,
                    'recipient_type' => $recipient->type,
                    'status' => 'failed',
                    'error_code' => $exception->getCode(),
                    'error' => $exception->getMessage(),
                ]);

                Log::channel('mail_audit')->warning('notifications_v2.mail.recipient_failed', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $dispatch->event_key,
                    'mailer' => $mailer,
                    'recipient_email' => $recipient->email,
                    'recipient_type' => $recipient->type,
                    'status' => 'failed',
                    'error_code' => $exception->getCode(),
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }

    private function sendProgramacion(string $to, array $bcc, array $snapshot, string $excel): void
    {
        $data = $snapshot['mail_data'];
        $mail = (new ProgramacionDiariaHtmlMail(
            $snapshot['fecha'],
            $data['juicios'],
            $data['lecturas'],
            $data['cortas'],
            $data['juecesAusentes'],
            $data['turno1'],
            $data['turno2'],
            $data['turno3'],
            $snapshot['firma_html'],
        ))->from($snapshot['from_address'], $snapshot['from_name'])
            ->attachData($excel, "Programacion_{$snapshot['fecha']}.xlsx", [
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

        Mail::mailer($snapshot['mailer'])->to($to)->bcc($bcc)->send($mail);
    }

    private function failureReason(Throwable $exception): string
    {
        $code = $exception->getCode();

        return $code ? "Rechazado por el servidor SMTP ({$code})." : 'Rechazado por el servidor SMTP.';
    }
}
