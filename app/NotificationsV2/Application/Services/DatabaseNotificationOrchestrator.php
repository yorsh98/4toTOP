<?php

namespace App\NotificationsV2\Application\Services;

use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Application\Contracts\RecipientResolver;
use App\NotificationsV2\Application\Jobs\ProcessNotificationDispatchJob;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\IdempotencyKeyFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class DatabaseNotificationOrchestrator implements NotificationOrchestrator
{
    public function __construct(private readonly RecipientResolver $recipientResolver) {}

    public function dispatch(string $typeCode, array $payload, array $options = []): NotificationDispatch
    {
        $notificationType = NotificationType::query()
            ->where('code', $typeCode)
            ->where('is_active', true)
            ->first();

        if (! $notificationType) {
            throw new RuntimeException("Notification type '{$typeCode}' is not active or does not exist.");
        }

        $idempotencyKey = (string) ($options['idempotency_key'] ?? IdempotencyKeyFactory::make($typeCode, $payload));
        $eventKey = (string) ($options['event_key'] ?? $typeCode);
        $scheduledFor = $this->parseScheduledFor($options['scheduled_for'] ?? null);
        $templateId = $options['template_id'] ?? null;

        $created = false;

        $dispatch = DB::transaction(function () use ($notificationType, $idempotencyKey, $eventKey, $payload, $scheduledFor, $templateId, $options, &$created): NotificationDispatch {
            $existing = NotificationDispatch::query()
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                Log::info('notifications_v2.dispatch.duplicate', [
                    'dispatch_id' => $existing->id,
                    'event_key' => $existing->event_key,
                    'idempotency_key' => $idempotencyKey,
                ]);

                return $existing;
            }

            $dispatch = NotificationDispatch::query()->create([
                'notification_type_id' => $notificationType->id,
                'template_id' => $templateId,
                'event_key' => $eventKey,
                'idempotency_key' => $idempotencyKey,
                'payload' => $payload,
                'status' => 'queued',
                'scheduled_for' => $scheduledFor,
                'queued_at' => now(),
            ]);

            $recipients = $this->recipientResolver->resolve($notificationType, $payload, $options);

            $copyContexts = $notificationType->copy_contexts;
            if (is_array($copyContexts) && $copyContexts !== []) {
                $copyRecipients = $this->recipientResolver->resolve($notificationType, $payload, [
                    'contexts' => $copyContexts,
                    'context_recipient_type' => 'cc',
                ]);

                $recipients = array_merge($recipients, $copyRecipients);
            }

            if ($recipients === []) {
                $dispatch->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'failure_reason' => 'No valid recipients resolved for dispatch.',
                ]);

                Log::warning('notifications_v2.dispatch.no_recipients', [
                    'dispatch_id' => $dispatch->id,
                    'event_key' => $eventKey,
                    'idempotency_key' => $idempotencyKey,
                ]);

                $created = true;

                return $dispatch;
            }

            foreach ($recipients as $recipient) {
                $dispatch->recipients()->create([
                    'type' => $recipient['type'] ?? 'to',
                    'name' => $recipient['name'] ?? null,
                    'email' => $recipient['email'] ?? null,
                    'context' => $recipient['context'] ?? null,
                    'status' => 'pending',
                ]);
            }

            $created = true;

            Log::info('notifications_v2.dispatch.created', [
                'dispatch_id' => $dispatch->id,
                'event_key' => $dispatch->event_key,
                'idempotency_key' => $dispatch->idempotency_key,
                'recipients_count' => count($recipients),
                'status' => $dispatch->status,
            ]);

            return $dispatch;
        });

        if ($created && $dispatch->status === 'queued' && config('notifications_v2.enabled', false)) {
            ProcessNotificationDispatchJob::dispatch($dispatch->id)
                ->onConnection((string) config('notifications_v2.queue.connection'))
                ->onQueue((string) config('notifications_v2.queue.name'))
                ->afterCommit();
        }

        return $dispatch;
    }

    private function parseScheduledFor(mixed $value): ?Carbon
    {
        if (! $value) {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        return Carbon::parse((string) $value);
    }
}
