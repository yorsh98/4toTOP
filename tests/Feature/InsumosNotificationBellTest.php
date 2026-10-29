<?php

use App\Livewire\InsumosNotificacionesPage;
use App\Livewire\InsumosNotificationBell;
use App\Models\User;
use App\Notifications\InsumosServiciosNotification;
use Livewire\Livewire;

test('bell shows unread count and polls visibly', function () {
    $user = User::factory()->create();
    $user->notify(new InsumosServiciosNotification('insumo.test.1', 'Aviso', 'Detalle', '/insumos/gestion'));

    Livewire::actingAs($user)->test(InsumosNotificationBell::class)
        ->assertSee('1 sin leer')
        ->assertSee('wire:poll.15s.visible', false);
});

test('marking notifications read dispatches the update event', function () {
    $user = User::factory()->create();
    $user->notify(new InsumosServiciosNotification('insumo.test.1', 'Aviso', 'Detalle', '/insumos/gestion'));

    Livewire::actingAs($user)->test(InsumosNotificacionesPage::class)
        ->call('marcarLeida', $user->notifications()->sole()->id)
        ->assertDispatched('notificaciones-actualizadas')
        ->call('marcarTodasLeidas')
        ->assertDispatched('notificaciones-actualizadas');
});

test('dashboard places actions in the header instead of the sidebar', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);
    $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();
    $sidebar = substr($html, strpos($html, '<nav '), strpos($html, '</nav>') - strpos($html, '<nav '));
    $header = substr($html, strpos($html, '<header class="bo-header'), strpos($html, '</header>') - strpos($html, '<header class="bo-header'));

    foreach (['Notificaciones', 'Ver sitio público', 'Cambiar tema'] as $label) {
        expect($header)->toContain($label)->and($sidebar)->not->toContain($label);
    }
});
