<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Livewire\Volt\Volt;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response
        ->assertOk()
        ->assertSeeVolt('pages.auth.register');
});

test('first user is automatically approved and becomes superadmin', function () {
    $component = Volt::test('pages.auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password');

    $component->call('register');

    $component->assertRedirect(route('login', absolute: false));

    $this->assertGuest();

    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
    ]);

    $user = User::query()->where('email', 'test@example.com')->firstOrFail();

    expect($user->approved_at)->not->toBeNull()
        ->and($user->hasRole('SuperAdmin'))->toBeTrue();
});

test('later users require approval', function () {
    User::factory()->create();

    Volt::test('pages.auth.register')
        ->set('name', 'Pending User')
        ->set('email', 'pending@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('login', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'pending@example.com',
        'approved_at' => null,
    ]);
});
