<?php

use App\Livewire\Ausentismo;
use App\Livewire\TablaGuias;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('tabla guias in full mode denies access without guias permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TablaGuias::class, ['modo' => 'full'])
        ->assertForbidden();
});

test('tabla guias in lectura mode allows public read access', function () {
    Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->assertSet('modo', 'lectura');
});

test('tabla guias create is forbidden without guias editar permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('guias.ver');

    $this->actingAs($user);

    Livewire::test(TablaGuias::class, ['modo' => 'full'])
        ->call('create')
        ->assertForbidden();
});

test('tabla guias create works when user has guias editar permission', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('guias.ver', 'guias.editar');

    $this->actingAs($user);

    Livewire::test(TablaGuias::class, ['modo' => 'full'])
        ->set('newNombre', 'Funcionario Prueba')
        ->set('newRut', '11111111-1')
        ->set('newEmail', 'funcionario@test.local')
        ->set('newTelefono1', '123456789')
        ->set('newTelefono2', '')
        ->set('newInstitucion', '1')
        ->call('create');

    $this->assertDatabaseHas('guias', [
        'nombre_completo' => 'Funcionario Prueba',
        'email' => 'funcionario@test.local',
    ]);
});

test('ausentismo component requires ausentismo permission in backoffice', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Ausentismo::class)
        ->assertForbidden();
});
