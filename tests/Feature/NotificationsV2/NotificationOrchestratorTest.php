<?php

use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Application\Jobs\ProcessNotificationDispatchJob;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationType;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    NotificationType::query()->updateOrCreate(
        ['code' => 'prueba.orquestador'],
        [
            'name' => 'Notificación de prueba',
            'description' => 'Evento de prueba del orquestador',
            'channel' => 'mail',
            'is_active' => true,
            'priority' => 'normal',
            'copy_contexts' => null,
        ]
    );
});

test('orchestrator queues processing job when feature flag is enabled', function () {
    config()->set('notifications_v2.enabled', true);
    Queue::fake();

    /** @var NotificationOrchestrator $orchestrator */
    $orchestrator = app(NotificationOrchestrator::class);

    $dispatch = $orchestrator->dispatch('prueba.orquestador', [
        'mensaje' => 'Notificación de prueba',
    ], [
        'recipients' => [[
            'type' => 'to',
            'name' => 'Funcionario Uno',
            'email' => 'funcionario.uno@example.cl',
        ]],
    ]);

    expect($dispatch->status)->toBe('queued');

    Queue::assertPushed(ProcessNotificationDispatchJob::class, function (ProcessNotificationDispatchJob $job) use ($dispatch) {
        return $job->dispatchId === $dispatch->id;
    });
});

test('orchestrator does not queue processing job when feature flag is disabled', function () {
    config()->set('notifications_v2.enabled', false);
    Queue::fake();

    /** @var NotificationOrchestrator $orchestrator */
    $orchestrator = app(NotificationOrchestrator::class);

    $dispatch = $orchestrator->dispatch('prueba.orquestador', [
        'mensaje' => 'Notificación de prueba',
    ], [
        'recipients' => [[
            'type' => 'to',
            'name' => 'Funcionario Uno',
            'email' => 'funcionario.uno@example.cl',
        ]],
    ]);

    expect($dispatch->status)->toBe('queued');
    Queue::assertNotPushed(ProcessNotificationDispatchJob::class);
});

test('orchestrator enforces idempotency for same event payload', function () {
    config()->set('notifications_v2.enabled', false);

    /** @var NotificationOrchestrator $orchestrator */
    $orchestrator = app(NotificationOrchestrator::class);

    $payload = [
        'mensaje' => 'Notificación de prueba',
    ];

    $options = [
        'recipients' => [[
            'type' => 'to',
            'name' => 'Funcionario Uno',
            'email' => 'funcionario.uno@example.cl',
        ]],
    ];

    $first = $orchestrator->dispatch('prueba.orquestador', $payload, $options);
    $second = $orchestrator->dispatch('prueba.orquestador', $payload, $options);

    expect($first->id)->toBe($second->id);
    expect(NotificationDispatch::query()->where('event_key', 'prueba.orquestador')->count())->toBe(1);
});
