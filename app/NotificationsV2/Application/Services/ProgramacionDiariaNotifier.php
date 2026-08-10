<?php

namespace App\NotificationsV2\Application\Services;

use App\Http\Controllers\PrograMailController;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Application\Jobs\ProcessNotificationDispatchJob;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use Illuminate\Support\Str;

class ProgramacionDiariaNotifier
{
    public function __construct(
        private readonly NotificationOrchestrator $orchestrator,
        private readonly PrograMailController $programacion,
    ) {}

    public function notify(string $fecha, array $emails, ?int $firmaId): NotificationDispatch
    {
        $this->ensureTypeAndTemplate();

        $recipients = collect($emails)
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->filter(fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->map(fn ($email) => ['type' => 'to', 'email' => $email, 'context' => 'programacion.diaria'])
            ->values()
            ->all();

        $dispatch = $this->orchestrator->dispatch('programacion.diaria', [
            'subject' => 'Programación Diaria 4°TOP',
            'programacion_diaria' => $this->programacion->snapshotForNotification($fecha, $firmaId),
        ], [
            'event_key' => 'programacion.diaria.'.$fecha,
            'idempotency_key' => 'programacion.diaria.'.$fecha.'.'.Str::uuid(),
            'recipients' => $recipients,
        ]);

        if (! config('notifications_v2.enabled', false) && $dispatch->status === 'queued') {
            ProcessNotificationDispatchJob::dispatch($dispatch->id)
                ->onConnection((string) config('notifications_v2.queue.connection'))
                ->onQueue((string) config('notifications_v2.queue.name'));
        }

        return $dispatch;
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'programacion.diaria'],
            [
                'name' => 'Programación diaria de audiencias',
                'description' => 'Difusión manual de la programación diaria.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'normal',
                'copy_contexts' => null,
            ],
        );

        NotificationTemplate::query()->firstOrCreate(
            ['notification_type_id' => $type->id, 'version' => 1],
            [
                'subject' => '{{ subject }}',
                'body_html' => '<p>Programación diaria.</p>',
                'body_text' => 'Programación diaria.',
                'variables_schema' => ['subject'],
                'is_active' => true,
                'activated_at' => now(),
            ],
        );
    }
}
