<?php

use App\Models\User;
use Livewire\Volt\Volt;

test('suspended user cannot authenticate', function () {
    $user = User::factory()->create([
        'suspended_at' => now(),
        'suspension_reason' => 'Prueba de suspensión',
    ]);

    $component = Volt::test('pages.auth.login')
        ->set('form.email', $user->email)
        ->set('form.password', 'password');

    $component->call('login');

    $component
        ->assertHasErrors(['form.email'])
        ->assertNoRedirect();

    $this->assertGuest();
});
