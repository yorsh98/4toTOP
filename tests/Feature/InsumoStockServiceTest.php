<?php

use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use App\Models\Sector;
use App\Models\User;
use App\Services\InsumoStockService;

function createInsumo(string $codigo): Insumo
{
    return Insumo::create([
        'codigo' => $codigo,
        'nombre' => "Insumo {$codigo}",
        'unidad_medida' => 'UN',
    ]);
}

test('entry creates stock and an auditable movement', function () {
    $insumo = createInsumo('INS-001');
    $sector = Sector::factory()->create();
    $actor = User::factory()->create();

    $movimiento = app(InsumoStockService::class)->ingresar($insumo->id, $sector->id, 20, $actor->id, 'Compra mensual');

    expect($movimiento->tipo)->toBe('entrada')
        ->and($movimiento->stock_anterior)->toBe(0)
        ->and($movimiento->stock_posterior)->toBe(20)
        ->and($movimiento->created_by_user_id)->toBe($actor->id)
        ->and($movimiento->operacion_uuid)->not->toBeEmpty();

    $this->assertDatabaseHas('insumo_existencias', [
        'insumo_id' => $insumo->id,
        'sector_id' => $sector->id,
        'cantidad' => 20,
    ]);
});

test('assignment reduces stock and records its recipient', function () {
    $insumo = createInsumo('INS-002');
    $sector = Sector::factory()->create();
    $actor = User::factory()->create();
    $recipient = User::factory()->create();
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 10]);

    $movimiento = app(InsumoStockService::class)
        ->asignar([['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 4]], $actor->id, $recipient->id)
        ->sole();

    expect($movimiento->tipo)->toBe('asignacion')
        ->and($movimiento->cantidad)->toBe(4)
        ->and($movimiento->stock_anterior)->toBe(10)
        ->and($movimiento->stock_posterior)->toBe(6)
        ->and($movimiento->asignado_a_user_id)->toBe($recipient->id);

    $this->assertDatabaseHas('insumo_existencias', [
        'insumo_id' => $insumo->id,
        'sector_id' => $sector->id,
        'cantidad' => 6,
    ]);
});

test('assignment rolls back every line when one item lacks stock', function () {
    $sector = Sector::factory()->create();
    $firstInsumo = createInsumo('INS-003');
    $secondInsumo = createInsumo('INS-004');
    $actor = User::factory()->create();
    $recipient = User::factory()->create();
    InsumoExistencia::create(['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 5]);
    InsumoExistencia::create(['insumo_id' => $secondInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 1]);

    expect(fn () => app(InsumoStockService::class)->asignar([
        ['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 2],
        ['insumo_id' => $secondInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 2],
    ], $actor->id, $recipient->id))->toThrow(DomainException::class);

    expect(InsumoExistencia::query()->where('insumo_id', $firstInsumo->id)->value('cantidad'))->toBe(5)
        ->and(InsumoExistencia::query()->where('insumo_id', $secondInsumo->id)->value('cantidad'))->toBe(1)
        ->and(InsumoMovimiento::query()->count())->toBe(0);
});

test('stock operations reject non-positive quantities and duplicate warehouse lines', function () {
    $insumo = createInsumo('INS-005');
    $sector = Sector::factory()->create();
    $actor = User::factory()->create();

    expect(fn () => app(InsumoStockService::class)->ingresar($insumo->id, $sector->id, 0, $actor->id))
        ->toThrow(InvalidArgumentException::class);
    expect(fn () => app(InsumoStockService::class)->asignar([
        ['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 1],
        ['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 1],
    ], $actor->id, $actor->id))->toThrow(InvalidArgumentException::class);
});

test('automatic assignment uses the largest stock first and splits only when needed', function () {
    $insumo = createInsumo('INS-AUTO');
    $firstSector = Sector::factory()->create();
    $secondSector = Sector::factory()->create();
    $thirdSector = Sector::factory()->create();
    $actor = User::factory()->create();
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $firstSector->id, 'cantidad' => 3]);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $secondSector->id, 'cantidad' => 7]);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $thirdSector->id, 'cantidad' => 5]);

    $movimientos = app(InsumoStockService::class)->asignarAutomatico([
        ['insumo_id' => $insumo->id, 'cantidad' => 10],
    ], $actor->id, null);

    expect($movimientos)->toHaveCount(2)
        ->and($movimientos->pluck('cantidad')->all())->toBe([7, 3])
        ->and($movimientos->pluck('operacion_uuid')->unique())->toHaveCount(1)
        ->and(InsumoExistencia::query()->where('sector_id', $secondSector->id)->value('cantidad'))->toBe(0)
        ->and(InsumoExistencia::query()->where('sector_id', $thirdSector->id)->value('cantidad'))->toBe(2)
        ->and(InsumoExistencia::query()->where('sector_id', $firstSector->id)->value('cantidad'))->toBe(3);
});

test('automatic assignment rolls back when aggregate stock is insufficient', function () {
    $insumo = createInsumo('INS-AUTO-FAIL');
    $firstSector = Sector::factory()->create();
    $secondSector = Sector::factory()->create();
    $actor = User::factory()->create();
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $firstSector->id, 'cantidad' => 3]);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $secondSector->id, 'cantidad' => 2]);

    expect(fn () => app(InsumoStockService::class)->asignarAutomatico([
        ['insumo_id' => $insumo->id, 'cantidad' => 6],
    ], $actor->id, null))->toThrow(DomainException::class);

    expect(InsumoExistencia::query()->where('sector_id', $firstSector->id)->value('cantidad'))->toBe(3)
        ->and(InsumoExistencia::query()->where('sector_id', $secondSector->id)->value('cantidad'))->toBe(2)
        ->and(InsumoMovimiento::query()->count())->toBe(0);
});
