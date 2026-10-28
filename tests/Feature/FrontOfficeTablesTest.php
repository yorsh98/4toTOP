<?php

use App\Models\TtyppRegistro;
use Carbon\Carbon;

test('ttypp muestra los datos y el detalle de la fecha seleccionada', function () {
    TtyppRegistro::factory()->create([
        'fecha_asignacion' => '2026-06-10',
        'rit' => '168-2026',
        'ruc' => '2600012345-6',
        'tipo_audiencia' => 'Juicio oral',
        'encargada_ti_mp' => 'María',
        'acta' => 'Acta Tres',
        'jueces' => 'Juez P',
        'sala' => null,
    ]);

    $this->get(route('ttypp.welcome', ['fecha' => '2026-06-10']))
        ->assertSuccessful()
        ->assertSee('168-2026')
        ->assertSee('2600012345-6')
        ->assertSee('Juicio oral')
        ->assertSee('María')
        ->assertSee('Acta Tres')
        ->assertSee('Juez P')
        ->assertSee('class="fo-dash"', false)
        ->assertSee('aria-expanded="false"', false);
});

test('ttypp informa cuando no hay registros', function () {
    $this->get(route('ttypp.welcome', ['fecha' => '2026-06-10']))
        ->assertSuccessful()
        ->assertSee('No hay registros TTyPP para miércoles 10 de junio de 2026. Prueba con otro día.')
        ->assertSee('Hoy');
});

test('ttypp permite navegar por fechas', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-09 10:00:00', 'America/Santiago'));

    try {
        $this->get(route('ttypp.welcome', ['fecha' => '2026-06-10']))
            ->assertSuccessful()
            ->assertSee('name="fecha" value="2026-06-10"', false)
            ->assertSee('?fecha=2026-06-09')
            ->assertSee('?fecha=2026-06-11');
    } finally {
        Carbon::setTestNow();
    }
});
