<?php

use App\Notifications\BitacoraPruebasNotification;
use App\NotificationsV2\Application\Services\BitacoraPruebasNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationRecipient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

test('bitacora pruebas notifier uses legacy notification when v2 is disabled', function () {
    config()->set('notifications_v2.enabled', false);
    Notification::fake();

    app(BitacoraPruebasNotifier::class)->notify(
        destinatarios: ['uno@example.cl', 'dos@example.cl'],
        senderName: 'Admin Demo',
        documentosUrl: 'https://example.cl/documentos',
        bitacoraPublicUrl: 'https://example.cl/bitacora-pruebas',
    );

    Notification::assertSentOnDemand(BitacoraPruebasNotification::class, 2);
    expect(NotificationDispatch::query()->count())->toBe(0);
});

test('bitacora pruebas notifier routes through v2 when enabled', function () {
    config()->set('notifications_v2.enabled', true);
    Notification::fake();
    Queue::fake();

    app(BitacoraPruebasNotifier::class)->notify(
        destinatarios: ['uno@example.cl', 'dos@example.cl', 'uno@example.cl'],
        senderName: 'Admin Demo',
        documentosUrl: 'https://example.cl/documentos',
        bitacoraPublicUrl: 'https://example.cl/bitacora-pruebas',
    );

    Notification::assertNothingSent();

    $dispatch = NotificationDispatch::query()->latest('id')->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->status)->toBe('queued');
    expect($dispatch->payload)->toMatchArray([
        'sender_name' => 'Admin Demo',
        'documentos_url' => 'https://example.cl/documentos',
        'bitacora_public_url' => 'https://example.cl/bitacora-pruebas',
    ]);

    expect(
        NotificationRecipient::query()
            ->where('dispatch_id', $dispatch->id)
            ->where('type', 'to')
            ->count()
    )->toBe(2);
});
