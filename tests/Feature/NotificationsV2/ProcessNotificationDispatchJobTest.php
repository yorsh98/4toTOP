<?php

use App\NotificationsV2\Application\Contracts\ChannelSender;
use App\NotificationsV2\Application\Contracts\TemplateRenderer;
use App\NotificationsV2\Application\Jobs\ProcessNotificationDispatchJob;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;

test('process notification job marks dispatch and recipient as sent', function () {
    config()->set('notifications_v2.mailer', 'array');

    $type = NotificationType::query()->create([
        'code' => 'test.job.sent',
        'name' => 'Test Job Sent',
        'description' => 'Template for successful job flow',
        'channel' => 'mail',
        'is_active' => true,
        'priority' => 'normal',
    ]);

    $template = NotificationTemplate::query()->create([
        'notification_type_id' => $type->id,
        'version' => 1,
        'subject' => 'Test {{ foo }}',
        'body_html' => '<p>Test {{ foo }}</p>',
        'body_text' => 'Test {{ foo }}',
        'variables_schema' => ['foo'],
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $dispatch = NotificationDispatch::query()->create([
        'notification_type_id' => $type->id,
        'template_id' => $template->id,
        'event_key' => 'test.job.sent',
        'idempotency_key' => hash('sha256', 'test.job.sent'),
        'payload' => ['foo' => 'bar'],
        'status' => 'queued',
        'queued_at' => now(),
    ]);

    $dispatch->recipients()->create([
        'type' => 'to',
        'name' => 'Receiver',
        'email' => 'receiver@example.cl',
        'context' => 'test',
        'status' => 'pending',
    ]);

    $job = new ProcessNotificationDispatchJob($dispatch->id);
    $job->handle(app(ChannelSender::class), app(TemplateRenderer::class));

    $dispatch->refresh();

    expect($dispatch->status)->toBe('sent');
    expect($dispatch->recipients()->first()?->status)->toBe('sent');
    expect($dispatch->attempts()->count())->toBe(1);
    expect($dispatch->attempts()->first()?->status)->toBe('sent');
});

test('process notification job keeps dispatch queued on handle exception and failed marks failed', function () {
    $type = NotificationType::query()->create([
        'code' => 'test.job.failure',
        'name' => 'Test Job Failure',
        'description' => 'Template for failure job flow',
        'channel' => 'mail',
        'is_active' => true,
        'priority' => 'normal',
    ]);

    $template = NotificationTemplate::query()->create([
        'notification_type_id' => $type->id,
        'version' => 1,
        'subject' => 'Test {{ foo }}',
        'body_html' => '<p>Test {{ foo }}</p>',
        'body_text' => 'Test {{ foo }}',
        'variables_schema' => ['foo'],
        'is_active' => true,
        'activated_at' => now(),
    ]);

    $dispatch = NotificationDispatch::query()->create([
        'notification_type_id' => $type->id,
        'template_id' => $template->id,
        'event_key' => 'test.job.failure',
        'idempotency_key' => hash('sha256', 'test.job.failure'),
        'payload' => ['foo' => 'bar'],
        'status' => 'queued',
        'queued_at' => now(),
    ]);

    $dispatch->recipients()->create([
        'type' => 'to',
        'name' => 'Receiver',
        'email' => 'receiver@example.cl',
        'context' => 'test',
        'status' => 'pending',
    ]);

    app()->bind(ChannelSender::class, fn () => new class implements ChannelSender
    {
        public function send(NotificationDispatch $dispatch, array $renderedContent): void
        {
            throw new RuntimeException('forced sender error');
        }
    });

    $job = new ProcessNotificationDispatchJob($dispatch->id);

    expect(fn () => $job->handle(app(ChannelSender::class), app(TemplateRenderer::class)))
        ->toThrow(RuntimeException::class, 'forced sender error');

    $dispatch->refresh();

    expect($dispatch->status)->toBe('queued');
    expect($dispatch->attempts()->count())->toBe(1);
    expect($dispatch->attempts()->first()?->status)->toBe('failed');

    $job->failed(new RuntimeException('job exhausted retries'));

    $dispatch->refresh();

    expect($dispatch->status)->toBe('failed');
    expect($dispatch->failure_reason)->toBe('job exhausted retries');
});
