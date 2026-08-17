<?php

use App\Livewire\ServiciosInventarioPage;
use App\Models\Activo;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\ServiciosRoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ServiciosRoleSeeder::class);
});

test('user without servicios permission cannot access inventory backoffice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('backoffice.servicios.inventario'))
        ->assertForbidden();
});

test('user with servicios ver permission can see inventory backoffice', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    $this->actingAs($user)
        ->get(route('backoffice.servicios.inventario'))
        ->assertSuccessful()
        ->assertSee('Inventario de servicios');
});

test('user with servicios crear permission can create activo', function () {
    $sector = Sector::factory()->create(['nombre' => 'Bodega Servicios']);
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.crear']);

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->set('nombre', 'Impresora de respaldo')
        ->set('sector_id', $sector->id)
        ->set('codigo_inventario', 'INV-SERV-100')
        ->set('numero_serie', 'SER-100')
        ->set('estado', 'Operativo')
        ->set('observaciones', 'Disponible para contingencia')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activos', [
        'nombre' => 'Impresora de respaldo',
        'sector_id' => $sector->id,
        'codigo_inventario' => 'INV-SERV-100',
    ]);
});

test('user with servicios editar permission can update activo', function () {
    $sector = Sector::factory()->create();
    $nuevoSector = Sector::factory()->create(['nombre' => 'Sala Nueva']);
    $activo = Activo::factory()->for($sector)->create([
        'nombre' => 'Monitor antiguo',
        'codigo_inventario' => 'INV-SERV-200',
    ]);
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.editar']);

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->call('edit', $activo->id)
        ->set('nombre', 'Monitor actualizado')
        ->set('sector_id', $nuevoSector->id)
        ->set('estado', 'Baja')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activos', [
        'id' => $activo->id,
        'nombre' => 'Monitor actualizado',
        'sector_id' => $nuevoSector->id,
        'estado' => 'Baja',
    ]);
});

test('user can move an asset between floor sectors', function () {
    $origen = Sector::factory()->create(['piso' => '7MO', 'nombre' => 'Sector origen']);
    $destino = Sector::factory()->create(['piso' => '9NO', 'nombre' => 'Sector destino']);
    $activo = Activo::factory()->for($origen)->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.editar']);

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->assertSee('Piso 7 · Sector origen')
        ->assertSee('Piso 9 · Sector destino')
        ->call('edit', $activo->id)
        ->set('sector_id', $destino->id)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('activos', ['id' => $activo->id, 'sector_id' => $destino->id]);
});

test('inventory form filters sectors by the selected floor', function () {
    $sectorPisoSiete = Sector::factory()->create(['piso' => '7MO', 'nombre' => 'Sector siete']);
    $sectorPisoOcho = Sector::factory()->create(['piso' => '8VO', 'nombre' => 'Sector ocho']);
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->set('sector_id', $sectorPisoOcho->id)
        ->call('selectPiso', 7)
        ->assertSet('piso', 7)
        ->assertSet('sector_id', null)
        ->assertViewHas('sectoresPiso', fn ($sectores) => $sectores->pluck('id')->all() === [$sectorPisoSiete->id]);
});

test('editing an asset selects its sector floor', function () {
    $sector = Sector::factory()->create(['piso' => '9NO']);
    $activo = Activo::factory()->for($sector)->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.editar']);

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->call('edit', $activo->id)
        ->assertSet('piso', 9)
        ->assertSet('sector_id', $sector->id);
});

test('inventory flash messages render as SweetAlert notifications', function () {
    $sector = Sector::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.crear']);

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->set('nombre', 'Teclado de prueba')
        ->set('sector_id', $sector->id)
        ->call('save')
        ->assertSeeHtml('data-flash-notification')
        ->assertSeeHtml('data-icon="success"');
});

test('user with servicios eliminar permission can delete activo', function () {
    $activo = Activo::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.eliminar']);

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->call('delete', $activo->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('activos', [
        'id' => $activo->id,
    ]);
});

test('search filters inventory list by sector and inventory code', function () {
    $sectorUno = Sector::factory()->create(['nombre' => 'Sala Inventario Uno']);
    $sectorDos = Sector::factory()->create(['nombre' => 'Sala Inventario Dos']);

    Activo::factory()->for($sectorUno)->create([
        'nombre' => 'Notebook listado',
        'codigo_inventario' => 'INV-BUSCADO',
    ]);

    Activo::factory()->for($sectorDos)->create([
        'nombre' => 'Telefono oculto',
        'codigo_inventario' => 'INV-OCULTO',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->set('search', 'INV-BUSCADO')
        ->assertSee('Notebook listado')
        ->assertDontSee('Telefono oculto')
        ->set('search', 'Sala Inventario Dos')
        ->assertSee('Telefono oculto')
        ->assertDontSee('Notebook listado');
});
