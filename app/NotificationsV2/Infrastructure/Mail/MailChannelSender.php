<?php

namespace App\NotificationsV2\Infrastructure\Mail;

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

        foreach ($dispatch->recipients as $recipient) {
            if ($recipient->status === 'sent') {
                continue;
            }

            try {
                Mail::mailer($mailer)->send([], [], function (Message $message) use ($recipient, $from, $subject, $html): void {
                    $message->to($recipient->email, $recipient->name ?: null);
                    $message->subject($subject);

                    if (! empty($from['address'])) {
                        $message->from((string) $from['address'], (string) ($from['name'] ?? 'Notificaciones'));
                    }

                    $message->html($html);
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
            } catch (Throwable $exception) {
                $recipient->update([
                    'status' => 'failed',
                    'failure_reason' => $exception->getMessage(),
                ]);

                Log::warning('notifications_v2.mail.recipient_failed', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $dispatch->event_key,
                    'recipient_email' => $recipient->email,
                    'recipient_type' => $recipient->type,
                    'status' => 'failed',
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
