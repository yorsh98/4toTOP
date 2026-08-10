<?php

namespace App\NotificationsV2\Application\Jobs;

use App\NotificationsV2\Application\Contracts\ChannelSender;
use App\NotificationsV2\Application\Contracts\TemplateRenderer;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessNotificationDispatchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [60, 300, 900];

    public function __construct(public readonly int $dispatchId)
    {
        $this->onConnection((string) config('notifications_v2.queue.connection'));
        $this->onQueue((string) config('notifications_v2.queue.name'));
    }

    public function handle(ChannelSender $channelSender, TemplateRenderer $templateRenderer): void
    {
        $dispatch = NotificationDispatch::query()
            ->with(['notificationType', 'template', 'recipients'])
            ->find($this->dispatchId);

        if (! $dispatch) {
            return;
        }

        if (! in_array($dispatch->status, ['queued', 'processing'], true)) {
            return;
        }

        $attempt = $dispatch->attempts()->create([
            'attempt_number' => $dispatch->attempts()->count() + 1,
            'channel' => $dispatch->notificationType?->channel ?? 'mail',
            'status' => 'processing',
            'started_at' => now(),
        ]);

        Log::info('notifications_v2.job.processing', [
            'dispatch_id' => $dispatch->id,
            'event_key' => $dispatch->event_key,
            'attempt_number' => $attempt->attempt_number,
            'status' => 'processing',
        ]);

        $dispatch->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);

        $template = $this->resolveTemplate($dispatch);

        if (! $template) {
            $attempt->update([
                'status' => 'failed',
                'error_class' => 'MissingTemplate',
                'error_message' => 'No active template found for this notification type.',
                'finished_at' => now(),
            ]);

            $dispatch->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => 'No active template found for this notification type.',
            ]);
            $this->clearProgramacionSnapshot($dispatch);

            return;
        }

        try {
            $rendered = $templateRenderer->render($template, $dispatch->payload ?? []);
            $channelSender->send($dispatch, $rendered);

            $dispatch->refresh()->loadMissing('recipients');

            $sentRecipients = $dispatch->recipients()->where('status', 'sent')->count();
            $failedRecipients = $dispatch->recipients()->where('status', 'failed')->count();

            if ($sentRecipients > 0) {
                $dispatch->update([
                    'status' => 'sent',
                    'sent_at' => now(),
                    'failure_reason' => $failedRecipients > 0
                        ? sprintf('%d destinatario(s) rechazado(s) por el servidor de correo.', $failedRecipients)
                        : null,
                ]);

                $attempt->update([
                    'status' => $failedRecipients > 0 ? 'sent_with_warnings' : 'sent',
                    'finished_at' => now(),
                ]);

                Log::info('notifications_v2.job.sent', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $dispatch->event_key,
                    'attempt_number' => $attempt->attempt_number,
                    'status' => $failedRecipients > 0 ? 'sent_with_warnings' : 'sent',
                    'sent_recipients' => $sentRecipients,
                    'failed_recipients' => $failedRecipients,
                ]);

                $this->clearProgramacionSnapshot($dispatch);

                return;
            }

            $dispatch->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $failedRecipients > 0
                    ? 'Todos los destinatarios fueron rechazados por el servidor de correo.'
                    : 'No se pudo enviar a ningun destinatario.',
            ]);

            $attempt->update([
                'status' => 'failed',
                'error_class' => 'NoRecipientsSent',
                'error_message' => 'No successful recipients in current attempt.',
                'finished_at' => now(),
            ]);

            Log::warning('notifications_v2.job.failed_recipients', [
                'dispatch_id' => $dispatch->id,
                'event_key' => $dispatch->event_key,
                'attempt_number' => $attempt->attempt_number,
                'status' => 'failed',
                'sent_recipients' => $sentRecipients,
                'failed_recipients' => $failedRecipients,
            ]);

            $this->clearProgramacionSnapshot($dispatch);
        } catch (Throwable $exception) {
            $attempt->update([
                'status' => 'failed',
                'error_class' => get_class($exception),
                'error_message' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            $dispatch->update([
                'status' => 'queued',
                'failure_reason' => $exception->getMessage(),
            ]);

            Log::warning('notifications_v2.job.retry', [
                'dispatch_id' => $dispatch->id,
                'event_key' => $dispatch->event_key,
                'attempt_number' => $attempt->attempt_number,
                'status' => 'queued',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        $dispatch = NotificationDispatch::query()->find($this->dispatchId);
        if (! $dispatch) {
            return;
        }

        $dispatch->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $exception->getMessage(),
        ]);

        Log::error('notifications_v2.job.failed', [
            'dispatch_id' => $dispatch->id,
            'event_key' => $dispatch->event_key,
            'status' => 'failed',
            'error' => $exception->getMessage(),
        ]);

        $this->clearProgramacionSnapshot($dispatch);
    }

    private function resolveTemplate(NotificationDispatch $dispatch): ?NotificationTemplate
    {
        if ($dispatch->template) {
            return $dispatch->template;
        }

        return NotificationTemplate::query()
            ->where('notification_type_id', $dispatch->notification_type_id)
            ->where('is_active', true)
            ->orderByDesc('version')
            ->first();
    }

    private function clearProgramacionSnapshot(NotificationDispatch $dispatch): void
    {
        $payload = $dispatch->payload ?? [];
        if (! array_key_exists('programacion_diaria', $payload)) {
            return;
        }

        unset($payload['programacion_diaria']);
        $dispatch->update(['payload' => $payload]);
    }
}
