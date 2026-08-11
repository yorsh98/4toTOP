<?php

use App\Livewire\AudienciaForm;
use App\Livewire\AudienciasCortasBatch;
use App\Livewire\AudienciasDiarias;
use App\Livewire\AudienciasDiariasTabla;
use App\Livewire\LecturasSentenciaBatch;
use App\Models\Audiencia;
use App\Models\Funcionario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);
});

test('audiencia form accepts valid names by configured roles', function () {
    Funcionario::create([
        'nombre' => 'Juez Audiencia',
        'rol' => 'juez',
        'email' => 'juez-audiencia@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Acta Audiencia',
        'rol' => 'sala_causa',
        'email' => 'acta-audiencia@example.cl',
        'fecha_cumpleanos' => '1981-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Encargada Causa',
        'rol' => 'encargado_causa',
        'email' => 'enc-causa@example.cl',
        'fecha_cumpleanos' => '1982-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'TTP Uno',
        'rol' => 'ttypp',
        'email' => 'ttp-uno@example.cl',
        'fecha_cumpleanos' => '1983-01-01',
    ]);

    Livewire::test(AudienciaForm::class)
        ->set('fecha', '2026-05-20')
        ->set('rit', '100-2026')
        ->set('sala', 'Sala 1')
        ->set('ubicacion', 'Piso 7')
        ->set('hora_inicio', '09:00')
        ->set('ruc', '12345678-9')
        ->set('cta_zoom', 'zoom-123')
        ->set('tipo_audiencia', 'Audiencia Corta')
        ->set('delito', 'Prueba')
        ->set('encargado_causa', 'Encargada Causa')
        ->set('encargado_ttp', 'TTP Uno')
        ->set('encargado_ttp_zoom', 'TTP Uno')
        ->set('JuezP', 'Juez Audiencia')
        ->set('JuezR', 'Juez Audiencia')
        ->set('JuezI', 'Juez Audiencia')
        ->set('acta', 'Acta Audiencia')
        ->set('anfitrion', 'Acta Audiencia')
        ->set('acusados', [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]])
        ->call('guardarAudiencia')
        ->assertHasNoErrors();

    expect(Audiencia::query()->where('rit', '100-2026')->exists())->toBeTrue();
});

test('lecturas batch allows free text while still suggesting catalog values', function () {
    Funcionario::create([
        'nombre' => 'No Juez',
        'rol' => 'sala_causa',
        'email' => 'nojuez@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Encargada Causa',
        'rol' => 'encargado_causa',
        'email' => 'enc-ls@example.cl',
        'fecha_cumpleanos' => '1982-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Acta LS',
        'rol' => 'sala_causa',
        'email' => 'acta-ls@example.cl',
        'fecha_cumpleanos' => '1981-01-01',
    ]);

    Livewire::test(LecturasSentenciaBatch::class)
        ->set('items.0.fecha', '2026-05-20')
        ->set('items.0.hora_inicio', '11:00')
        ->set('items.0.JuezR', 'No Juez')
        ->set('items.0.tipo_audiencia', 'Lectura de Sentencia')
        ->set('items.0.sala', 'Sala 2')
        ->set('items.0.cta_zoom', 'zoom-222')
        ->set('items.0.rit', '200-2026')
        ->set('items.0.ruc', '22222222-2')
        ->set('items.0.encargado_sala', 'Encargada Causa')
        ->set('items.0.acta', 'Acta LS')
        ->set('items.0.acusados', [[
            'nombre_completo' => 'Acusado LS',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]])
        ->call('guardar')
        ->assertHasNoErrors();

    expect(
        Audiencia::query()
            ->where('rit', '200-2026')
            ->where('JuezR', 'No Juez')
            ->exists()
    )->toBeTrue();
});

test('daily hearings list defers secondary fields until its detail is opened', function () {
    $audiencia = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '300-2026',
        'ruc' => '33333333-3',
        'sala' => 'Sala 3',
        'hora_inicio' => '10:00',
        'tipo_audiencia' => 'Juicio Oral',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 3',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
        'obs' => 'Detalle reservado',
    ]);
    $lectura = $audiencia->replicate();
    $lectura->rit = '301-2026';
    $lectura->tipo_audiencia = 'Lectura de Sentencia';
    $lectura->save();

    $corta = $audiencia->replicate();
    $corta->rit = '302-2026';
    $corta->tipo_audiencia = 'Audiencia Corta';
    $corta->save();

    Livewire::test(AudienciasDiariasTabla::class, ['fecha' => '2026-05-20'])
        ->assertSee('300-2026')
        ->assertDontSee('Detalle reservado')
        ->call('toggleDetalle', $audiencia->id)
        ->assertSee('Detalle reservado')
        ->call('editar', $audiencia->id)
        ->assertDispatched('editar-audiencia');
});

test('daily hearings page opens the module matching the stored hearing type', function () {
    $lectura = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '350-2026',
        'ruc' => '35035035-0',
        'sala' => 'Sala 3',
        'hora_inicio' => '10:00',
        'tipo_audiencia' => 'Lectura de Sentencia',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 3',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
    ]);
    $corta = $lectura->replicate();
    $corta->rit = '351-2026';
    $corta->tipo_audiencia = 'Audiencia Corta';
    $corta->save();

    Livewire::test(AudienciasDiarias::class)
        ->dispatch('editar-audiencia', id: $lectura->id)
        ->assertSet('editId', $lectura->id)
        ->assertSet('editTab', 'lecturas')
        ->assertSeeLivewire(LecturasSentenciaBatch::class)
        ->assertDispatched('audiencia-edicion-abierta')
        ->call('closeEdit')
        ->assertSet('editId', null)
        ->assertDispatched('audiencia-edicion-cerrada');

    Livewire::test(AudienciasDiarias::class)
        ->dispatch('editar-audiencia', id: $corta->id)
        ->assertSet('editTab', 'cortas')
        ->assertSeeLivewire(AudienciasCortasBatch::class);
});

test('editing a matching hearing updates its existing date RIT and type record', function () {
    $audiencia = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '400-2026',
        'ruc' => '44444444-4',
        'cta_zoom' => 'zoom-444',
        'sala' => 'Sala 4',
        'hora_inicio' => '10:30',
        'tipo_audiencia' => 'Juicio Oral',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 4',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
        'obs' => 'Observación original',
    ]);

    Livewire::test(AudienciaForm::class, ['edit' => $audiencia->id])
        ->assertSet('audienciaId', $audiencia->id)
        ->assertSet('hora_inicio', '10:30')
        ->set('obs', 'Observación actualizada')
        ->call('guardarAudiencia')
        ->assertDispatched('audiencia-guardada');

    expect(Audiencia::query()
        ->whereDate('fecha', '2026-05-20')
        ->where('rit', '400-2026')
        ->where('tipo_audiencia', 'Juicio Oral')
        ->count())->toBe(1);
    expect($audiencia->refresh()->obs)->toBe('Observación actualizada');
});

test('editing a hearing for another day preserves the historical record', function () {
    $audiencia = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '500-2026',
        'ruc' => '55555555-5',
        'cta_zoom' => 'zoom-555',
        'sala' => 'Sala 5',
        'hora_inicio' => '11:00',
        'tipo_audiencia' => 'Juicio Oral',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 5',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
        'obs' => 'Histórico',
    ]);

    Livewire::test(AudienciaForm::class, ['edit' => $audiencia->id])
        ->set('fecha', '2026-05-21')
        ->set('obs', 'Programación nueva')
        ->call('guardarAudiencia');

    expect(Audiencia::query()->where('rit', '500-2026')->count())->toBe(2);
    expect($audiencia->refresh()->obs)->toBe('Histórico');
    expect(Audiencia::query()->whereDate('fecha', '2026-05-21')->where('rit', '500-2026')->value('obs'))->toBe('Programación nueva');
});

test('editing a hearing with another type creates an independent record', function () {
    $audiencia = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '600-2026',
        'ruc' => '66666666-6',
        'cta_zoom' => 'zoom-666',
        'sala' => 'Sala 6',
        'hora_inicio' => '12:00',
        'tipo_audiencia' => 'Juicio Oral',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 6',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
    ]);

    Livewire::test(AudienciaForm::class, ['edit' => $audiencia->id])
        ->set('tipo_audiencia', 'Audiencia Corta')
        ->call('guardarAudiencia');

    expect(Audiencia::query()->whereDate('fecha', '2026-05-20')->where('rit', '600-2026')->count())->toBe(2);
    expect($audiencia->refresh()->tipo_audiencia)->toBe('Juicio Oral');
});

test('sentence reading batch loads and updates its selected hearing', function () {
    $audiencia = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '700-2026',
        'ruc' => '77777777-7',
        'sala' => 'Sala 7',
        'hora_inicio' => '13:00',
        'cta_zoom' => 'zoom-700',
        'tipo_audiencia' => 'Lectura de Sentencia',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 7',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
        'obs' => 'Lectura original',
    ]);

    Livewire::test(LecturasSentenciaBatch::class, ['edit' => $audiencia->id])
        ->assertSet('editId', $audiencia->id)
        ->assertSet('items.0.rit', '700-2026')
        ->assertSet('items.0.hora_inicio', '13:00')
        ->set('items.0.obs', 'Lectura actualizada')
        ->call('guardar')
        ->assertDispatched('audiencia-guardada');

    expect(Audiencia::query()->where('rit', '700-2026')->count())->toBe(1);
    expect($audiencia->refresh()->obs)->toBe('Lectura actualizada');
});

test('short hearings batch loads and updates its selected hearing', function () {
    $audiencia = Audiencia::create([
        'fecha' => '2026-05-20',
        'rit' => '800-2026',
        'ruc' => '88888888-8',
        'sala' => 'Sala 8',
        'ubicacion' => 'Piso 8',
        'hora_inicio' => '14:00',
        'cta_zoom' => 'zoom-800',
        'tipo_audiencia' => 'Audiencia Corta',
        'encargado_causa' => 'Encargada Causa',
        'acta' => 'Acta 8',
        'acusados' => [[
            'nombre_completo' => 'Acusado Prueba',
            'situacion' => 'Libre',
            'medida_cautelar' => '',
            'forma_notificacion' => '',
        ]],
        'estado' => 'POR_REALIZARSE',
        'JuezP' => 'Juez Presidente',
        'JuezR' => 'Juez Redactor',
        'JuezI' => 'Juez Integrante',
        'anfitrion' => 'Anfitrión',
        'obs' => 'Corta original',
    ]);

    Livewire::test(AudienciasCortasBatch::class, ['edit' => $audiencia->id])
        ->assertSet('editId', $audiencia->id)
        ->assertSet('shared.hora_inicio', '14:00')
        ->assertSet('items.0.rit', '800-2026')
        ->set('items.0.obs', 'Corta actualizada')
        ->call('guardar')
        ->assertDispatched('audiencia-guardada');

    expect(Audiencia::query()->where('rit', '800-2026')->count())->toBe(1);
    expect($audiencia->refresh()->obs)->toBe('Corta actualizada');
});
