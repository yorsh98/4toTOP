<?php

use App\Livewire\AudienciasDiariasCorreo;
use App\Mail\ProgramacionDiariaHtmlMail;
use App\Models\User;
use App\NotificationsV2\Application\Contracts\ChannelSender;
use App\NotificationsV2\Application\Contracts\TemplateRenderer;
use App\NotificationsV2\Application\Jobs\ProcessNotificationDispatchJob;
use App\NotificationsV2\Application\Services\ProgramacionDiariaNotifier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

test('daily program dispatch sends one frozen message per recipient', function () {
    config()->set('notifications_v2.enabled', false);
    config()->set('mail.default', 'array');
    Mail::fake();

    $dispatch = app(ProgramacionDiariaNotifier::class)->notify('2026-08-10', [
        'first@example.cl',
        'second@example.cl',
    ], null);

    (new ProcessNotificationDispatchJob($dispatch->id))->handle(
        app(ChannelSender::class),
        app(TemplateRenderer::class),
    );

    $dispatch->refresh();

    expect($dispatch->status)->toBe('sent');
    expect($dispatch->recipients()->where('status', 'sent')->count())->toBe(2);
    expect($dispatch->payload)->not->toHaveKey('programacion_diaria');

    Mail::assertSent(ProgramacionDiariaHtmlMail::class, 2);
});

test('daily hearings page queues the program dispatch without waiting for SMTP', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);

    config()->set('notifications_v2.enabled', true);
    Queue::fake();

    Livewire::test(AudienciasDiariasCorreo::class, ['fecha' => '2026-08-10'])
        ->set('recipients', ['receiver@example.cl'])
        ->call('enviarCorreoDifusion')
        ->assertHasNoErrors()
        ->assertSet('envioStatus', 'queued')
        ->assertSet('envioTotal', 1);

    Queue::assertPushed(ProcessNotificationDispatchJob::class);
});
