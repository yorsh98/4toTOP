<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('superadmin cannot remove own superadmin role from security module', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('SuperAdmin');

    $this->actingAs($superAdmin)
        ->put(route('seguridad.users.roles.update', $superAdmin), [
            'roles' => ['AdminOperacion'],
        ])
        ->assertSessionHas('error');

    expect($superAdmin->fresh()->hasRole('SuperAdmin'))->toBeTrue();
});

test('system cannot be left without at least one superadmin', function () {
    $actor = User::factory()->create();
    $targetSuperAdmin = User::factory()->create();

    $actor->givePermissionTo('navbar.seguridad.visible', 'navbar.seguridad.editable');
    $targetSuperAdmin->assignRole('SuperAdmin');

    $this->actingAs($actor)
        ->put(route('seguridad.users.roles.update', $targetSuperAdmin), [
            'roles' => ['Operador'],
        ])
        ->assertSessionHas('error');

    expect($targetSuperAdmin->fresh()->hasRole('SuperAdmin'))->toBeTrue();
});
