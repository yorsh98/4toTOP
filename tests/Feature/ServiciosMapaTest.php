<?php

use App\Livewire\ServiciosMapaPage;
use App\Livewire\ServiciosMapaPublicPage;
use App\Models\Activo;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\SectorSeeder;
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
        ->assertSee('Mapa interactivo')
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

test('user with edit permission can rename a sector from the map', function () {
    $sector = Sector::factory()->create([
        'nombre' => '823 Amelia Muñoz',
        'identificador_mapa' => '823_OF',
    ]);

    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.editar']);

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectHotspot', '823_OF')
        ->call('editSector', $sector->id)
        ->set('sectorNombre', '823 Nueva persona')
        ->call('saveSector')
        ->assertSee('823 Nueva persona');

    $this->assertDatabaseHas('sectors', [
        'id' => $sector->id,
        'identificador_mapa' => '823_OF',
        'nombre' => '823 Nueva persona',
    ]);
});

test('user can rename sectors from every floor', function (int $floor, string $piso, string $identifier) {
    $sector = Sector::query()->updateOrCreate(
        ['identificador_mapa' => $identifier],
        ['piso' => $piso, 'nombre' => 'Nombre inicial']
    );
    $user = User::factory()->create();
    $user->givePermissionTo(['servicios.ver', 'servicios.editar']);

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectPiso', $floor)
        ->call('selectHotspot', $identifier)
        ->call('editSector', $sector->id)
        ->set('sectorNombre', "Nombre piso {$floor}")
        ->call('saveSector')
        ->assertSee("Nombre piso {$floor}");

    $this->assertDatabaseHas('sectors', ['id' => $sector->id, 'nombre' => "Nombre piso {$floor}"]);
})->with([
    [7, '7MO', '7_713'],
    [8, '8VO', 'TTyPP'],
    [9, '9NO', '9_TERESA'],
]);

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
        ->assertSeeHtml('placeholder="Buscar en inventario..."')
        ->set('inventorySearch', 'SEARCH-001')
        ->assertSee('Notebook buscable mapa')
        ->assertDontSee('Impresora oculta mapa')
        ->assertSee('1 registro');
});

test('map and inventory searches filter independently', function () {
    $sectorMapa = Sector::factory()->create(['identificador_mapa' => '837']);
    $sectorTabla = Sector::factory()->create(['identificador_mapa' => '836']);
    Activo::factory()->for($sectorMapa)->create(['nombre' => 'Activo mapa', 'codigo_inventario' => 'MAP-001']);
    Activo::factory()->for($sectorTabla)->create(['nombre' => 'Activo tabla', 'codigo_inventario' => 'TAB-001']);
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->set('mapSearch', 'MAP-001')
        ->assertSet('inventorySearch', '')
        ->assertSee('1 sector encontrado en el plano')
        ->assertSeeHtml('border-cyan-300 bg-cyan-600/80 text-white ring-4 ring-cyan-200')
        ->set('inventorySearch', 'TAB-001')
        ->assertSet('mapSearch', 'MAP-001')
        ->assertSee('Activo tabla')
        ->assertDontSee('Activo mapa');
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
        ->assertSee('Inventario')
        ->assertSee('Unidad de Servicios')
        ->assertSee('Plano interactivo y activos registrados en inventario.')
        ->assertSee('Meson publico visible')
        ->assertSee('PRIV-001')
        ->assertSee('SERIE-PRIVADA-001')
        ->assertDontSee('Ver inventario')
        ->assertSeeHtml('<th class="px-3 py-2 text-left font-semibold text-slate-700">Código</th>')
        ->assertSeeHtml('<th class="px-3 py-2 text-left font-semibold text-slate-700">Serie</th>')
        ->assertDontSeeHtml('<th class="px-3 py-2 text-left font-semibold text-slate-700">Piso</th>')
        ->assertDontSeeHtml('<th class="px-3 py-2 text-left font-semibold text-slate-700">Estado</th>')
        ->assertDontSee('Observacion interna privada');
});

test('stock is visible only on the private services map', function () {
    $sector = Sector::factory()->create(['nombre' => 'Bodega con insumos', 'identificador_mapa' => '837']);
    $insumo = Insumo::create(['codigo' => 'MAP-INS-001', 'nombre' => 'Insumo confidencial', 'unidad_medida' => 'UN']);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 12]);
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectHotspot', '837')
        ->assertSee('Stock en bodegas')
        ->assertSee('Insumo confidencial')
        ->assertSee('12 unidades de insumos')
        ->assertSee('0 / 12')
        ->assertDontSeeHtml('bg-emerald-600');

    Livewire::test(ServiciosMapaPublicPage::class)
        ->call('selectHotspot', '837')
        ->assertDontSee('Stock en bodegas')
        ->assertDontSee('Insumo confidencial')
        ->assertDontSee('MAP-INS-001');
});

test('private map search finds sectors by insumo code and name', function () {
    $sector = Sector::factory()->create(['nombre' => 'Bodega buscable', 'identificador_mapa' => '837']);
    $insumo = Insumo::create(['codigo' => 'MAP-SEARCH-INS', 'nombre' => 'Toner buscable', 'unidad_medida' => 'UN']);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 4]);
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->set('mapSearch', 'SEARCH-INS')
        ->assertSee('1 sector encontrado en el plano')
        ->assertSeeHtml('border-cyan-300 bg-cyan-600/80 text-white ring-4 ring-cyan-200');
});

test('guest search filters public map inventory without navigation', function () {
    $sector = Sector::factory()->create([
        'identificador_mapa' => '837',
    ]);

    Activo::factory()->for($sector)->create([
        'nombre' => 'Activo publico encontrado',
        'codigo_inventario' => 'PUBLIC-CODE-001',
        'numero_serie' => 'PUBLIC-SERIE-001',
    ]);
    Activo::factory()->for($sector)->create(['nombre' => 'Activo publico oculto']);

    Livewire::test(ServiciosMapaPublicPage::class)
        ->set('search', 'encontrado')
        ->assertSee('Activo publico encontrado')
        ->assertDontSee('Activo publico oculto')
        ->set('search', 'CODE-001')
        ->assertSee('Activo publico encontrado')
        ->assertDontSee('Activo publico oculto')
        ->set('search', 'SERIE-001')
        ->assertSee('Activo publico encontrado')
        ->assertDontSee('Activo publico oculto');
});

test('guest search highlights matching sectors on the public map', function () {
    $sectorEncontrado = Sector::factory()->create(['identificador_mapa' => '837']);
    $sectorOculto = Sector::factory()->create(['identificador_mapa' => '836']);
    Activo::factory()->for($sectorEncontrado)->create(['codigo_inventario' => 'PUBLIC-HIGHLIGHT-001']);
    Activo::factory()->for($sectorOculto)->create();

    Livewire::test(ServiciosMapaPublicPage::class)
        ->set('search', 'HIGHLIGHT-001')
        ->assertSee('1 sector encontrado en el plano')
        ->assertSeeHtml('border-cyan-300 bg-cyan-600/80 text-white ring-2 ring-cyan-200')
        ->assertSeeHtml('opacity-30');
});

test('guest can see public asset codes and serial numbers without observations', function () {
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
        ->assertSee('Detalle de sector')
        ->assertSee('811 Sector publico detalle')
        ->assertSee('Notebook publico detalle')
        ->assertSee('MAP-PRIVATE-001')
        ->assertSee('Serie: SN-PRIVATE-001')
        ->assertDontSee('Uso interno servicios');
});

test('guest can switch floors on public services map through query string', function () {
    $this->get(route('servicios.mapa-publico', ['piso' => 7]))
        ->assertSuccessful()
        ->assertSee('Piso 7')
        ->assertSee('images/planos/Plano_Piso_7.webp', false);

    $this->get(route('servicios.mapa-publico', ['piso' => 9]))
        ->assertSuccessful()
        ->assertSee('Piso 9')
        ->assertSee('images/planos/Plano_Piso_9.webp', false);
});

test('guest can switch floors on public services map without navigation', function () {
    Livewire::test(ServiciosMapaPublicPage::class)
        ->call('selectPiso', 7)
        ->assertSet('piso', 7)
        ->assertSee('images/planos/Plano_Piso_7.webp', false);
});

test('public inventory table only shows assets from the selected floor', function () {
    $sectorPisoSiete = Sector::factory()->create(['piso' => '7MO']);
    $sectorPisoNueve = Sector::factory()->create(['piso' => '9NO']);

    Activo::factory()->for($sectorPisoSiete)->create(['nombre' => 'Activo piso siete']);
    Activo::factory()->for($sectorPisoNueve)->create(['nombre' => 'Activo piso nueve']);

    Livewire::test(ServiciosMapaPublicPage::class)
        ->call('selectPiso', 7)
        ->assertSee('Activo piso siete')
        ->assertDontSee('Activo piso nueve')
        ->call('selectPiso', 9)
        ->assertSee('Activo piso nueve')
        ->assertDontSee('Activo piso siete');
});

test('floors seven and nine load their administrable hotspots', function (int $floor, string $piso, string $identifier, string $nombre) {
    $sector = Sector::query()->updateOrCreate(
        ['identificador_mapa' => $identifier],
        ['piso' => $piso, 'nombre' => $nombre]
    );

    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectPiso', $floor)
        ->call('selectHotspot', $identifier)
        ->assertSet('selectedSectorId', $sector->id)
        ->assertSee($nombre);
})->with([
    [7, '7MO', '7_713', '713 Oficina administrable'],
    [9, '9NO', '9_934', '934 Oficina administrable'],
    [7, '7MO', '7_HALL_CENTRAL', 'HALL CENTRAL'],
    [7, '7MO', '7_ESPACIO_INFERIOR', 'por designar'],
    [7, '7MO', '7_por designar', 'por designar'],
    [7, '7MO', '7_Publico', 'Publico'],
    [7, '7MO', '7_Zona Publica', 'Zona Publica'],
    [8, '8VO', 'ADMINISTRACION_HALL', 'Adm.'],
    [8, '8VO', 'SERVICIOS_HALL', 'Servicios'],
    [8, '8VO', 'ADM', 'ADM'],
    [8, '8VO', 'ESPACIO_INFERIOR', 'Encargados'],
    [8, '8VO', 'Zona Publica', 'Zona Publica'],
    [9, '9NO', '9_HALL_CENTRAL', 'HALL CENTRAL'],
    [9, '9NO', '9_Causas_1', 'Causas_1'],
    [9, '9NO', '9_Causas_3', 'Causas_3'],
    [9, '9NO', '9_ESPACIO_INFERIOR_1', 'Causas_2'],
    [9, '9NO', '9_ESPACIO_INFERIOR_2', 'Informaticos'],
    [9, '9NO', '9_Zona Publica', 'Zona Publica'],
    [8, '8VO', 'TTyPP', 'TTyPP'],
    [9, '9NO', '9_PECERA_9', 'Pecera 9'],
    [9, '9NO', '9_TERESA', 'Teresa'],
    [9, '9NO', '9_904_OF', '904'],
    [9, '9NO', '9_DASH_1', '-'],
    [9, '9NO', '9_DASH_2', '-'],
]);

test('map hotspots show their label and accessible name', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->call('selectPiso', 7)
        ->assertSeeHtml('aria-label="713, 0 activos"')
        ->assertSeeHtml('<span class="drop-shadow">713</span>');
});

test('map uses blue colors for populated and empty sectors', function () {
    $poblado = Sector::factory()->create(['identificador_mapa' => '837']);
    $vacio = Sector::factory()->create(['identificador_mapa' => '836']);
    Activo::factory()->for($poblado)->create();
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosMapaPage::class)
        ->assertSeeHtml('border-blue-700 bg-blue-600/75 text-white')
        ->assertSeeHtml('border-blue-300 bg-blue-200/70 text-blue-950');

    Livewire::test(ServiciosMapaPublicPage::class)
        ->assertSeeHtml('border-blue-700 bg-blue-600/75 text-white')
        ->assertSeeHtml('border-blue-300 bg-blue-200/70 text-blue-950');
});

test('sector seeder registers map sectors for floors seven and nine', function () {
    $this->seed(SectorSeeder::class);

    $this->assertDatabaseHas('sectors', [
        'piso' => '7MO',
        'identificador_mapa' => '7_713',
        'nombre' => '713',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_934',
        'nombre' => '934',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '7MO',
        'identificador_mapa' => '7_HALL_CENTRAL',
        'nombre' => 'HALL CENTRAL',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_HALL_CENTRAL',
        'nombre' => 'HALL CENTRAL',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_PECERA_9',
        'nombre' => 'Pecera 9',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '8VO',
        'identificador_mapa' => 'TTyPP',
        'nombre' => 'TTyPP',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '8VO',
        'identificador_mapa' => 'ADMINISTRACION_HALL',
        'nombre' => 'Adm.',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '8VO',
        'identificador_mapa' => 'SERVICIOS_HALL',
        'nombre' => 'Servicios',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '8VO',
        'identificador_mapa' => 'ADM',
        'nombre' => 'ADM',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_Causas_1',
        'nombre' => 'Causas_1',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_Causas_3',
        'nombre' => 'Causas_3',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '8VO',
        'identificador_mapa' => 'ESPACIO_INFERIOR',
        'nombre' => 'Encargados',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '7MO',
        'identificador_mapa' => '7_ESPACIO_INFERIOR',
        'nombre' => 'por designar',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '8VO',
        'identificador_mapa' => 'Zona Publica',
        'nombre' => 'Zona Publica',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '7MO',
        'identificador_mapa' => '7_Zona Publica',
        'nombre' => 'Zona Publica',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_Zona Publica',
        'nombre' => 'Zona Publica',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_ESPACIO_INFERIOR_1',
        'nombre' => 'Causas_2',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_ESPACIO_INFERIOR_2',
        'nombre' => 'Informaticos',
    ]);

    $this->assertDatabaseHas('sectors', [
        'piso' => '9NO',
        'identificador_mapa' => '9_DASH_1',
        'nombre' => '-',
    ]);
});
