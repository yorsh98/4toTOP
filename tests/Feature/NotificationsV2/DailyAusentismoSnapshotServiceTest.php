<?php

use App\Models\Ausentismo;
use App\Services\DailyAusentismoSnapshotService;

test('snapshot service returns active absences for provided date', function () {
    Ausentismo::query()->create([
        'funcionario_nombre' => 'Funcionario Vigente',
        'cargo' => 'Juez/a',
        'tipo_permiso' => 'Licencia Medica',
        'fecha_inicio' => '2026-04-20',
        'fecha_termino' => '2026-04-22',
        'observacion' => 'Control',
    ]);

    Ausentismo::query()->create([
        'funcionario_nombre' => 'Funcionario No Vigente',
        'cargo' => 'Administrativo/a',
        'tipo_permiso' => 'Feriado Legal',
        'fecha_inicio' => '2026-04-24',
        'fecha_termino' => '2026-04-25',
        'observacion' => 'Vacaciones',
    ]);

    $snapshot = app(DailyAusentismoSnapshotService::class)->forDate('2026-04-21');

    expect($snapshot['fecha_reporte'])->toBe('2026-04-21');
    expect($snapshot['total_ausentes'])->toBe(1);
    expect($snapshot['ausentes'])->toHaveCount(1);
    expect($snapshot['ausentes'][0])->toMatchArray([
        'funcionario_nombre' => 'Funcionario Vigente',
        'cargo' => 'Juez/a',
        'tipo_permiso' => 'Licencia Medica',
        'fecha_inicio' => '2026-04-20',
        'fecha_termino' => '2026-04-22',
        'observacion' => 'Control',
    ]);
});

test('snapshot service returns empty list when there are no active absences', function () {
    Ausentismo::query()->create([
        'funcionario_nombre' => 'Funcionario Fuera De Rango',
        'cargo' => 'Juez/a',
        'tipo_permiso' => 'Licencia Medica',
        'fecha_inicio' => '2026-04-10',
        'fecha_termino' => '2026-04-11',
        'observacion' => 'Control',
    ]);

    $snapshot = app(DailyAusentismoSnapshotService::class)->forDate('2026-04-21');

    expect($snapshot['fecha_reporte'])->toBe('2026-04-21');
    expect($snapshot['total_ausentes'])->toBe(0);
    expect($snapshot['ausentes'])->toBe([]);
});
