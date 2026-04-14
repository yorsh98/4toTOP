<?php

use App\Livewire\AudienciaForm;
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
