<?php

use App\Livewire\Security\UsersRolesPage;
use App\Models\User;
use Livewire\Livewire;
use Livewire\Volt\Volt;

test('pending user cannot authenticate until approved', function () {
    $user = User::factory()->create([
        'approved_at' => null,
        'approved_by' => null,
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

test('superadmin can approve pending user from seguridad module', function () {
    $superAdmin = User::factory()->create(['email' => 'admin-approval@test.local']);
    $superAdmin->assignRole('SuperAdmin');

    $pending = User::factory()->create([
        'approved_at' => null,
        'approved_by' => null,
    ]);

    $this->actingAs($superAdmin);

    Livewire::test(UsersRolesPage::class)
        ->call('approveUser', $pending->id)
        ->assertHasNoErrors();

    $pending->refresh();

    expect($pending->approved_at)->not->toBeNull();
    expect($pending->approved_by)->toBe($superAdmin->id);
    expect($pending->rejected_at)->toBeNull();

    $this->assertDatabaseHas('security_audits', [
        'action' => 'user.approved',
        'entity_type' => 'user',
        'entity_id' => (string) $pending->id,
    ]);
});
