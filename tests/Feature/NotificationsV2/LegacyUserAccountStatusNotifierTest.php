<?php

use App\Models\User;
use App\Notifications\UserAccountStatusNotification;
use App\NotificationsV2\Application\Services\LegacyUserAccountStatusNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationRecipient;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

test('legacy account status notifier uses legacy notification when v2 is disabled', function () {
    config()->set('notifications_v2.enabled', false);
    Notification::fake();

    $user = User::factory()->create();

    app(LegacyUserAccountStatusNotifier::class)->notify($user, 'approved', 'Admin Demo');

    Notification::assertSentTo($user, UserAccountStatusNotification::class);
    expect(NotificationDispatch::query()->count())->toBe(0);
});

test('legacy account status notifier routes through v2 when enabled', function () {
    config()->set('notifications_v2.enabled', true);
    Notification::fake();
    Queue::fake();

    $user = User::factory()->create([
        'email' => 'status.user@example.cl',
        'name' => 'Status User',
    ]);

    app(LegacyUserAccountStatusNotifier::class)->notify($user, 'rejected', 'Admin Demo');

    Notification::assertNothingSent();

    $dispatch = NotificationDispatch::query()->latest('id')->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->event_key)->toBe("legacy.user_account_status.rejected.{$user->id}");
    expect($dispatch->status)->toBe('queued');
    expect($dispatch->payload)->toMatchArray([
        'status' => 'rejected',
        'user_name' => 'Status User',
        'admin_name' => 'Admin Demo',
    ]);

    expect(
        NotificationRecipient::query()
            ->where('dispatch_id', $dispatch->id)
            ->where('email', 'status.user@example.cl')
            ->where('type', 'to')
            ->exists()
    )->toBeTrue();
});
