<?php

use App\Models\Funcionario;
use App\Services\FuncionarioCatalog;

test('it exposes configured roles and labels', function () {
    $catalog = app(FuncionarioCatalog::class);

    expect($catalog->roleOptions())
        ->toHaveKey('juez', 'Juez')
        ->toHaveKey('ttypp', 'TTyPP');

    expect($catalog->roles())
        ->toContain('juez', 'sala_causa', 'ttypp', 'encargado_causa', 'jefatura', 'informatico');
});

test('it resolves names and emails by context', function () {
    Funcionario::create([
        'nombre' => 'Juez Uno',
        'rol' => 'juez',
        'email' => 'juez1@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Jefatura Uno',
        'rol' => 'jefatura',
        'email' => 'jefatura1@example.cl',
        'fecha_cumpleanos' => '1982-02-02',
    ]);

    Funcionario::create([
        'nombre' => 'Informatica Uno',
        'rol' => 'informatico',
        'email' => 'informatica1@example.cl',
        'fecha_cumpleanos' => '1983-03-03',
    ]);

    $catalog = app(FuncionarioCatalog::class);

    expect($catalog->nombresByContext('turnos.magistrados'))
        ->toBe(['Juez Uno']);

    expect($catalog->emailsByContext('oficios.solicitantes'))
        ->toContain('jefatura1@example.cl', 'informatica1@example.cl')
        ->not->toContain('juez1@example.cl');
});

test('it resolves destinatarios by context with unique emails', function () {
    Funcionario::create([
        'nombre' => 'Jefatura Uno',
        'rol' => 'jefatura',
        'email' => 'jefatura-destino@example.cl',
        'fecha_cumpleanos' => '1982-02-02',
    ]);

    Funcionario::create([
        'nombre' => 'Informatica Uno',
        'rol' => 'informatico',
        'email' => 'informatica-destino@example.cl',
        'fecha_cumpleanos' => '1983-03-03',
    ]);

    Funcionario::create([
        'nombre' => 'Juez Excluido',
        'rol' => 'juez',
        'email' => 'juez@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    $catalog = app(FuncionarioCatalog::class);
    $destinatarios = $catalog->destinatariosByContext('oficios.solicitantes');

    expect($destinatarios)
        ->toHaveCount(2)
        ->and($destinatarios[0])
        ->toHaveKeys(['nombre', 'email', 'rol', 'rol_label']);

    expect(collect($destinatarios)->pluck('email')->all())
        ->toContain('jefatura-destino@example.cl', 'informatica-destino@example.cl')
        ->not->toContain('juez@example.cl');
});

test('it resolves birthday lists by day and month', function () {
    Funcionario::create([
        'nombre' => 'Cumple Hoy',
        'rol' => 'sala_causa',
        'email' => 'hoy@example.cl',
        'fecha_cumpleanos' => '1990-04-14',
    ]);

    Funcionario::create([
        'nombre' => 'Cumple Abril',
        'rol' => 'ttypp',
        'email' => 'abril@example.cl',
        'fecha_cumpleanos' => '1988-04-20',
    ]);

    Funcionario::create([
        'nombre' => 'Cumple Otro Mes',
        'rol' => 'juez',
        'email' => 'otro@example.cl',
        'fecha_cumpleanos' => '1987-05-20',
    ]);

    $catalog = app(FuncionarioCatalog::class);

    expect($catalog->cumpleanerosDelDia('2026-04-14'))
        ->toHaveCount(1)
        ->and($catalog->cumpleanerosDelDia('2026-04-14')[0])
        ->toMatchArray([
            'nombre' => 'Cumple Hoy',
            'fecha_cumpleanos' => '1990-04-14',
        ]);

    expect($catalog->cumpleanerosDelMes(4))
        ->toHaveCount(2)
        ->sequence(
            fn ($item) => $item->toMatchArray(['nombre' => 'Cumple Abril']),
            fn ($item) => $item->toMatchArray(['nombre' => 'Cumple Hoy']),
        );
});
