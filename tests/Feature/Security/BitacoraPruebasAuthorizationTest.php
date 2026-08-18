<?php

use App\Livewire\BitacoraPruebasPage;
use App\Models\BitacoraPruebaDia;
use App\Models\BitacoraPruebaItem;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('bitacora pruebas public route is accessible without authentication', function () {
    $this->get(route('bitacora-pruebas.public'))
        ->assertSuccessful();
});

test('guest is redirected when trying to access bitacora pruebas backoffice', function () {
    $this->get(route('bitacora-pruebas.index'))
        ->assertRedirect(route('login'));
});

test('authenticated user without permissions gets forbidden on bitacora pruebas backoffice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('bitacora-pruebas.index'))
        ->assertForbidden();
});

test('operador can access bitacora pruebas backoffice and create dia and prueba', function () {
    $user = User::factory()->create();
    $user->assignRole('Operador');

    $this->actingAs($user)
        ->get(route('bitacora-pruebas.index'))
        ->assertSuccessful();

    Livewire::actingAs($user)
        ->test(BitacoraPruebasPage::class)
        ->set('rit', '100-2026')
        ->set('numeroDia', 1)
        ->set('fechaDia', '2026-04-20')
        ->call('createDia')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');

    $diaId = BitacoraPruebaDia::query()->where('rit', '100-2026')->where('numero_dia', 1)->value('id');

    Livewire::actingAs($user)
        ->test(BitacoraPruebasPage::class)
        ->set("newItems.{$diaId}.nombre_prueba", 'Declaracion de testigo')
        ->set("newItems.{$diaId}.institucion_exhibe", 'Min. Publico')
        ->call('addItem', $diaId)
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');

    $this->assertDatabaseHas('bitacora_pruebas_items', [
        'bitacora_pruebas_dia_id' => $diaId,
        'nombre_prueba' => 'Declaracion de testigo',
        'institucion_exhibe' => 'Min. Publico',
        'ingresado_por_user_id' => $user->id,
    ]);
});

test('rit datalist suggests only rit values entered by authenticated user', function () {
    $userA = User::factory()->create();
    $userA->assignRole('Operador');

    $userB = User::factory()->create();

    $diaA = BitacoraPruebaDia::query()->create([
        'rit' => '200-2026',
        'numero_dia' => 1,
        'fecha_dia' => '2026-04-20',
    ]);

    $diaB = BitacoraPruebaDia::query()->create([
        'rit' => '999-2026',
        'numero_dia' => 1,
        'fecha_dia' => '2026-04-20',
    ]);

    BitacoraPruebaItem::query()->create([
        'bitacora_pruebas_dia_id' => $diaA->id,
        'nombre_prueba' => 'Prueba A',
        'institucion_exhibe' => 'Min. Publico',
        'ingresado_por_user_id' => $userA->id,
    ]);

    BitacoraPruebaItem::query()->create([
        'bitacora_pruebas_dia_id' => $diaB->id,
        'nombre_prueba' => 'Prueba B',
        'institucion_exhibe' => 'Def. DPP',
        'ingresado_por_user_id' => $userB->id,
    ]);

    Livewire::actingAs($userA)
        ->test(BitacoraPruebasPage::class)
        ->assertSee('200-2026')
        ->assertDontSee('999-2026');
});
