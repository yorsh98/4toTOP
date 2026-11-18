<?php

use App\Livewire\Security\UsersRolesPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\SuperAdminSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('superadmin cannot remove own superadmin role from security module', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('SuperAdmin');

    $this->actingAs($superAdmin);

    Livewire::test(UsersRolesPage::class)
        ->set("selectedRoles.{$superAdmin->id}", ['AdminOperacion'])
        ->call('saveUserRoles', $superAdmin->id)
        ->assertHasNoErrors();

    expect($superAdmin->fresh()->hasRole('SuperAdmin'))->toBeTrue();
});

test('system cannot be left without at least one superadmin', function () {
    $actor = User::factory()->create();
    $targetSuperAdmin = User::factory()->create();

    $actor->givePermissionTo('navbar.seguridad.visible', 'navbar.seguridad.editable');
    $targetSuperAdmin->assignRole('SuperAdmin');

    $this->actingAs($actor);

    Livewire::test(UsersRolesPage::class)
        ->set("selectedRoles.{$targetSuperAdmin->id}", ['Operador'])
        ->call('saveUserRoles', $targetSuperAdmin->id)
        ->assertHasNoErrors();

    expect($targetSuperAdmin->fresh()->hasRole('SuperAdmin'))->toBeTrue();
});

test('the administrative seeder does not provision a new account', function () {
    $this->seed(SuperAdminSeeder::class);

    $this->assertDatabaseCount('users', 0);
});
