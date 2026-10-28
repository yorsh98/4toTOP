<?php

use App\Livewire\TablaGuias;
use App\Models\Guias;
use App\Models\TtyppRegistro;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

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

test('guías públicas muestran contactos y enlaces', function () {
    Guias::create([
        'nombre_completo' => 'María Pérez',
        'institucion' => 2,
        'telefono1' => '+56 2 1234 5678',
        'email' => 'maria@example.test',
    ]);

    $this->get(route('guiastelefonicas'))
        ->assertSuccessful()
        ->assertSee('María Pérez')
        ->assertSee('Gendarmeria')
        ->assertSee('href="tel:+56212345678"', false)
        ->assertSee('href="mailto:maria@example.test"', false);
});

test('guías públicas permiten buscar y muestran estados vacíos', function () {
    Guias::create(['nombre_completo' => 'María Pérez', 'institucion' => 2]);

    Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->set('search', 'María')
        ->assertSee('María Pérez')
        ->set('search', 'No existe')
        ->assertDontSee('María Pérez')
        ->assertSee('Ningún contacto coincide');

    Guias::query()->delete();
    Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->assertSee('Aún no hay guías cargadas.');
});

test('guías en modo full conservan la vista del backoffice', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('guias.ver');
    $this->actingAs($user);

    Livewire::test(TablaGuias::class, ['modo' => 'full'])
        ->assertViewIs('livewire.tabla-guias');
});
