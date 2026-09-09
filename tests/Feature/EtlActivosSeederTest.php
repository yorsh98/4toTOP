<?php

use App\Livewire\ServiciosInventarioPage;
use App\Models\Activo;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\EtlActivosSeeder;
use Database\Seeders\ServiciosRoleSeeder;
use Livewire\Livewire;

test('ETL activos seeder imports both sheets without duplicates on rerun', function () {
    $this->seed(EtlActivosSeeder::class);

    expect(Activo::query()->count())->toBe(1292);

    $s13 = Sector::query()->where('identificador_mapa', 's13')->firstOrFail();
    $s03 = Sector::query()->where('identificador_mapa', 's03')->firstOrFail();

    expect($s13->piso)->toBe('SUBTERRANEO')
        ->and($s03->piso)->toBe('SUBTERRANEO')
        ->and(Activo::query()->where('sector_id', $s13->id)->count())->toBe(11)
        ->and(Activo::query()->where('sector_id', $s03->id)->count())->toBe(44)
        ->and(Activo::query()->where('sector_id', $s03->id)->where('estado', 'Baja')->count())->toBe(44)
        ->and(Activo::query()->where('sector_id', '!=', $s03->id)->where('estado', 'Operativo')->count())->toBe(1248);

    $this->assertDatabaseHas('activos', [
        'nombre' => 'Pantalla HP P22',
        'numero_serie' => '3CM;15025QM',
        'sector_id' => Sector::query()->where('identificador_mapa', '9_926')->value('id'),
    ]);

    $this->assertDatabaseHas('activos', [
        'nombre' => 'HP LaserJet Managed E40040dn',
        'numero_serie' => 'PHCNG04856',
        'sector_id' => Sector::query()->where('identificador_mapa', '829')->value('id'),
    ]);

    $this->assertDatabaseHas('activos', [
        'numero_serie' => '4CE510BK7B',
        'sector_id' => Sector::query()->where('identificador_mapa', '9_906')->value('id'),
    ]);

    $this->assertDatabaseHas('activos', [
        'numero_serie' => '74634C6600844',
        'sector_id' => Sector::query()->where('identificador_mapa', '9_HALL_CENTRAL')->value('id'),
    ]);

    expect(Activo::query()->where('codigo_inventario', '1700032082')->count())->toBe(2);

    $adm = Sector::query()->where('identificador_mapa', 'ADM')->firstOrFail();
    expect(Activo::query()
        ->whereIn('origen_importacion', collect(range(445, 450))
            ->map(fn (int $row) => "[ETL AF modificado.xlsx:Hoja1:{$row}]")
            ->all())
        ->where('sector_id', $adm->id)
        ->count())->toBe(6);

    $this->assertDatabaseHas('activos', [
        'origen_importacion' => '[ETL AF modificado.xlsx:Hoja1:2]',
        'observaciones' => null,
    ]);

    $this->seed(EtlActivosSeeder::class);

    expect(Activo::query()->count())->toBe(1292);
});

test('inventory form lists underground sectors', function () {
    $sector = Sector::factory()->create(['piso' => 'SUBTERRANEO', 'nombre' => 'Bodega subterránea']);
    $this->seed(ServiciosRoleSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('servicios.ver');

    Livewire::actingAs($user)
        ->test(ServiciosInventarioPage::class)
        ->call('selectPiso', 0)
        ->assertSet('piso', 0)
        ->assertViewHas('sectoresPiso', fn ($sectores) => $sectores->pluck('id')->all() === [$sector->id]);
});
