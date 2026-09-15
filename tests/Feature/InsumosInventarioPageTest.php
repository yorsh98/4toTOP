<?php

use App\Livewire\InsumosInventarioPage;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('inventory page creates, edits and deactivates an insumo', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.inventario');

    Livewire::actingAs($user)
        ->test(InsumosInventarioPage::class)
        ->set('codigo', 'INV-001')
        ->set('nombre', 'Toner negro')
        ->set('stock_minimo', 8)
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast')
        ->assertDontSeeHtml('wire:model="unidad_medida"');

    $insumo = Insumo::query()->where('codigo', 'INV-001')->firstOrFail();

    Livewire::actingAs($user)
        ->test(InsumosInventarioPage::class)
        ->call('editar', $insumo->id)
        ->set('stock_minimo', 12)
        ->call('guardar')
        ->call('alternarActivo', $insumo->id)
        ->assertHasNoErrors();

    expect($insumo->fresh()->stock_minimo)->toBe(12)
        ->and($insumo->fresh()->activo)->toBeFalse()
        ->and($insumo->fresh()->unidad_medida)->toBe('UN');
});

test('inventory page records replenishment through a stock movement', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('insumos.inventario');
    $insumo = Insumo::create([
        'codigo' => 'INV-002',
        'nombre' => 'Carpeta',
        'unidad_medida' => 'UN',
    ]);
    $sector = Sector::factory()->create();

    Livewire::actingAs($user)
        ->test(InsumosInventarioPage::class)
        ->call('abrirReposicion', $insumo->id)
        ->assertSee('Cerrar')
        ->set('reposicionSectorId', $sector->id)
        ->set('reposicionCantidad', 25)
        ->set('reposicionObservacion', 'Compra trimestral')
        ->call('registrarReposicion')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');

    expect(InsumoExistencia::query()->where('insumo_id', $insumo->id)->where('sector_id', $sector->id)->value('cantidad'))->toBe(25)
        ->and(InsumoMovimiento::query()->where('insumo_id', $insumo->id)->value('tipo'))->toBe('entrada')
        ->and(InsumoMovimiento::query()->where('insumo_id', $insumo->id)->value('created_by_user_id'))->toBe($user->id);

    Livewire::actingAs($user)
        ->test(InsumosInventarioPage::class)
        ->call('abrirReposicion', $insumo->id)
        ->call('cerrarReposicion')
        ->assertDontSee('Registrar reposicion');
});

test('inventory page paginates the latest fifteen movements', function () {
    $user = User::factory()->create(['name' => 'Responsable inventario']);
    $user->givePermissionTo('insumos.inventario');
    $recipient = User::factory()->create(['name' => 'Persona asignada']);
    $insumo = Insumo::create(['codigo' => 'INV-003', 'nombre' => 'Resma carta', 'unidad_medida' => 'UN']);
    $sector = Sector::factory()->create();

    foreach (range(1, 16) as $number) {
        InsumoMovimiento::create([
            'operacion_uuid' => (string) Str::uuid(), 'insumo_id' => $insumo->id, 'sector_id' => $sector->id,
            'tipo' => 'asignacion', 'cantidad' => $number, 'stock_anterior' => 20, 'stock_posterior' => 20 - $number,
            'asignado_a_user_id' => $recipient->id, 'created_by_user_id' => $user->id,
            'observacion' => "Movimiento {$number}",
        ]);
    }

    Livewire::actingAs($user)
        ->test(InsumosInventarioPage::class)
        ->assertSeeInOrder(['Responsable', 'Asignado a', 'Tipo', 'Cantidad'])
        ->assertSee('Responsable inventario')
        ->assertSee('Persona asignada')
        ->assertSeeHtml("wire:click=\"nextPage('movimientosPage')\"");
});

test('inventory page is forbidden without its permission', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(InsumosInventarioPage::class)
        ->assertForbidden();
});
