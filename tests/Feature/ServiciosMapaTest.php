<?php

use App\Livewire\ServiciosMapaPage;
use App\Livewire\ServiciosMapaPublicPage;
use App\Models\Activo;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\ServiciosRoleSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(ServiciosRoleSeeder::class);
});

test('user without servicios permission cannot access services map', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('backoffice.servicios.mapa'))
        ->assertForbidden();
});

test('user with servicios ver permission can see services map', function () {
    $sector = Sector::factory()->create([
        'nombre' => '837 Sector Mapa',
        'identificador_mapa' => '837',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Activo visible mapa',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    $this->actingAs($user)
        ->get(route('backoffice.servicios.mapa'))
        ->assertSuccessful()
        ->assertSee('Servicios - Mapa interactivo')
        ->assertSee('Plano real Piso 8')
        ->assertSee('PLANTA: Piso 8')
        ->assertSee('837')
        ->assertSee('1 registro');
});

test('selecting sector loads sector assets detail', function () {
    $sector = Sector::factory()->create([
        'nombre' => 'Bodega interactiva',
        'identificador_mapa' => '811',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Notebook detalle mapa',
        'codigo_inventario' => 'MAP-001',
        'estado' => 'Operativo',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectHotspot', '811')
        ->assertSet('selectedSectorId', $sector->id)
        ->assertSet('selectedHotspotIdentifier', '811')
        ->assertSee('Bodega interactiva')
        ->assertSee('Notebook detalle mapa')
        ->assertSee('MAP-001');
});

test('search filters map inventory table', function () {
    $sector = Sector::factory()->create([
        'nombre' => '837 Sector Mapa',
        'identificador_mapa' => '837',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Notebook buscable mapa',
        'codigo_inventario' => 'MAP-SEARCH-001',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Impresora oculta mapa',
        'codigo_inventario' => 'MAP-HIDDEN-001',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->set('search', 'SEARCH-001')
        ->assertSee('Notebook buscable mapa')
        ->assertDontSee('Impresora oculta mapa')
        ->assertSee('1 registro');
});

test('sector without assets shows empty state in detail panel', function () {
    $sector = Sector::factory()->create([
        'nombre' => 'Sector sin activos',
        'identificador_mapa' => '820',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectSector', $sector->id)
        ->assertSee('Este sector no tiene activos registrados.');
});

test('guest can access public services map', function () {
    $sector = Sector::factory()->create([
        'nombre' => '837 Sector publico',
        'identificador_mapa' => '837',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Meson publico visible',
        'codigo_inventario' => 'PRIV-001',
        'numero_serie' => 'SERIE-PRIVADA-001',
        'observaciones' => 'Observacion interna privada',
    ]);

    $this->get(route('servicios.mapa-publico'))
        ->assertSuccessful()
        ->assertSee('Mapa Servicios')
        ->assertSee('Unidad de Servicios del Tribunal')
        ->assertSee('Plano interactivo y activos registrados en inventario.')
        ->assertSee('Meson publico visible')
        ->assertDontSee('Ver inventario')
        ->assertDontSee('PRIV-001')
        ->assertDontSee('SERIE-PRIVADA-001')
        ->assertDontSee('Observacion interna privada');
});

test('guest can see public assets without sensitive fields', function () {
    $sector = Sector::factory()->create([
        'nombre' => '811 Sector publico detalle',
        'identificador_mapa' => '811',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Notebook publico detalle',
        'codigo_inventario' => 'MAP-PRIVATE-001',
        'numero_serie' => 'SN-PRIVATE-001',
        'observaciones' => 'Uso interno servicios',
    ]);

    Livewire::test(ServiciosMapaPublicPage::class)
        ->assertSet('publicMode', true)
        ->call('selectHotspot', '811')
        ->assertSee('811 Sector publico detalle')
        ->assertSee('Notebook publico detalle')
        ->assertDontSee('MAP-PRIVATE-001')
        ->assertDontSee('SN-PRIVATE-001')
        ->assertDontSee('Uso interno servicios');
});

test('guest can switch floors on public services map through query string', function () {
    $this->get(route('servicios.mapa-publico', ['piso' => 7]))
        ->assertSuccessful()
        ->assertSee('Piso 7')
        ->assertSee('images/planos/Plano_Piso_7.png', false);

    $this->get(route('servicios.mapa-publico', ['piso' => 9]))
        ->assertSuccessful()
        ->assertSee('Piso 9')
        ->assertSee('images/planos/Plano_Piso_9.png', false);
});
