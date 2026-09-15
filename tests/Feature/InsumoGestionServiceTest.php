<?php

use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use App\Models\Sector;
use App\Models\User;
use App\Services\InsumoGestionService;
use App\Services\InsumoSolicitudService;

function createGestionInsumo(string $codigo): Insumo
{
    return Insumo::create([
        'codigo' => $codigo,
        'nombre' => "Insumo {$codigo}",
        'unidad_medida' => 'UN',
    ]);
}

test('requested items can be delivered partially and then completed', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();
    $sector = Sector::factory()->create();
    $insumo = createGestionInsumo('GES-001');
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 8]);
    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $insumo->id, 'cantidad' => 5]], $recipient->id);
    $detalle = $solicitud->detalles->sole();
    $gestion = app(InsumoGestionService::class);

    $gestion->entregar($solicitud, [['solicitud_detalle_id' => $detalle->id, 'sector_id' => $sector->id, 'cantidad' => 2]], $actor->id);

    expect($solicitud->fresh()->estado)->toBe('parcial')
        ->and(InsumoExistencia::query()->where('insumo_id', $insumo->id)->value('cantidad'))->toBe(6)
        ->and((int) InsumoMovimiento::query()->where('solicitud_detalle_id', $detalle->id)->sum('cantidad'))->toBe(2);

    $gestion->entregar($solicitud, [['solicitud_detalle_id' => $detalle->id, 'sector_id' => $sector->id, 'cantidad' => 3]], $actor->id);

    expect($solicitud->fresh()->estado)->toBe('completada')
        ->and(InsumoExistencia::query()->where('insumo_id', $insumo->id)->value('cantidad'))->toBe(3);
});

test('delivery rolls back every line when stock is insufficient', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();
    $sector = Sector::factory()->create();
    $firstInsumo = createGestionInsumo('GES-002');
    $secondInsumo = createGestionInsumo('GES-003');
    InsumoExistencia::create(['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 4]);
    InsumoExistencia::create(['insumo_id' => $secondInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 1]);
    $solicitud = app(InsumoSolicitudService::class)->crear([
        ['insumo_id' => $firstInsumo->id, 'cantidad' => 2],
        ['insumo_id' => $secondInsumo->id, 'cantidad' => 2],
    ], $recipient->id);

    expect(fn () => app(InsumoGestionService::class)->entregar($solicitud, $solicitud->detalles->map(fn ($detalle): array => [
        'solicitud_detalle_id' => $detalle->id,
        'sector_id' => $sector->id,
        'cantidad' => 2,
    ])->all(), $actor->id))->toThrow(DomainException::class);

    expect($solicitud->fresh()->estado)->toBe('pendiente')
        ->and(InsumoExistencia::query()->where('insumo_id', $firstInsumo->id)->value('cantidad'))->toBe(4)
        ->and(InsumoExistencia::query()->where('insumo_id', $secondInsumo->id)->value('cantidad'))->toBe(1)
        ->and(InsumoMovimiento::query()->count())->toBe(0);
});

test('direct assignment targets catalog funcionarios with or without a user account', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create(['email' => 'funcionario@example.cl']);
    $funcionario = Funcionario::create([
        'nombre' => 'Funcionario con cuenta', 'rol' => 'informatico', 'email' => $recipient->email, 'fecha_cumpleanos' => '1990-01-01',
    ]);
    $sinCuenta = Funcionario::create([
        'nombre' => 'Funcionario sin cuenta', 'rol' => 'informatico', 'email' => 'sin-cuenta@example.cl', 'fecha_cumpleanos' => '1991-01-01',
    ]);
    $sector = Sector::factory()->create();
    $firstInsumo = createGestionInsumo('GES-004');
    $secondInsumo = createGestionInsumo('GES-005');
    InsumoExistencia::create(['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 3]);
    InsumoExistencia::create(['insumo_id' => $secondInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 4]);

    $movimientos = app(InsumoGestionService::class)->asignarDirecto([
        ['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 1],
        ['insumo_id' => $secondInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 2],
    ], $actor->id, $funcionario->id, 'Entrega directa');

    expect($movimientos)->toHaveCount(2)
        ->and($movimientos->pluck('asignado_a_user_id')->unique()->all())->toBe([$recipient->id])
        ->and($movimientos->pluck('asignado_a_funcionario_id')->unique()->all())->toBe([$funcionario->id])
        ->and($movimientos->pluck('operacion_uuid')->unique())->toHaveCount(1);

    $sinCuentaMovimientos = app(InsumoGestionService::class)->asignarDirecto([
        ['insumo_id' => $firstInsumo->id, 'sector_id' => $sector->id, 'cantidad' => 1],
    ], $actor->id, $sinCuenta->id);

    expect($sinCuentaMovimientos->sole()->asignado_a_funcionario_id)->toBe($sinCuenta->id)
        ->and($sinCuentaMovimientos->sole()->asignado_a_user_id)->toBeNull();
});

test('only pending requests can be rejected with a reason', function () {
    $actor = User::factory()->create();
    $recipient = User::factory()->create();
    $insumo = createGestionInsumo('GES-006');
    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $insumo->id, 'cantidad' => 1]], $recipient->id);

    app(InsumoGestionService::class)->rechazar($solicitud, $actor->id, 'Sin stock disponible');

    expect($solicitud->fresh()->estado)->toBe('rechazada')
        ->and($solicitud->fresh()->rechazada_por_user_id)->toBe($actor->id)
        ->and($solicitud->fresh()->motivo_rechazo)->toBe('Sin stock disponible');
});
