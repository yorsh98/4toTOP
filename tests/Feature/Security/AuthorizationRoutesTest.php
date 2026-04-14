<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('guest is redirected when trying to access security roles route', function () {
    $this->get(route('seguridad.roles.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user without security permission gets forbidden on security roles route', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('seguridad.roles.index'))
        ->assertForbidden();
});

test('superadmin can access security roles route', function () {
    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('SuperAdmin');

    $this->actingAs($superAdmin)
        ->get(route('seguridad.roles.index'))
        ->assertOk();
});

test('public front routes remain accessible without authentication', function () {
    $this->get('/tablas')->assertOk();
    $this->get('/tabla2')->assertOk();
    $this->get('/SistOficioLibertades')->assertOk();
    $this->get('/guiastelefonicas')->assertOk();
});

test('monitor route is publicly accessible without authentication', function () {
    $this->get('/Monitor')->assertOk();
});

test('monitor route is accessible for authenticated user without permission', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/Monitor')
        ->assertOk();
});
