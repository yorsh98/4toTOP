<?php

use App\Models\Funcionario;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);
});

test('turno assigned outlook preview route renders', function () {
    $this->get(route('preview.notifications-v2.turno-asignado'))
        ->assertSuccessful()
        ->assertSee('4to Tribunal de Juicio Oral en lo Penal de Santiago')
        ->assertSee('Turno Semanal')
        ->assertSee('Juez del Turno')
        ->assertSee('Funcionarios');
});

test('turno preview consumes real turno data from system', function () {
    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'TM1' => 'JUEZ REAL',
        'TJ1' => 'JEFE REAL',
        'TF1' => 'FUNCIONARIO REAL UNO',
        'TF1_CARGO' => 'Encargado de Acta',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
        'FECHA' => 'Semana del 20-04-2026 al 24-04-2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Funcionario::query()->create([
        'nombre' => 'JUEZ REAL',
        'rol' => 'juez',
        'email' => 'juez.real@example.cl',
        'telefono' => '900000001',
        'run' => '1-9',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'JEFE REAL',
        'rol' => 'jefatura',
        'email' => 'jefe.real@example.cl',
        'telefono' => '900000002',
        'run' => '2-7',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'FUNCIONARIO REAL UNO',
        'rol' => 'sala_causa',
        'email' => 'funcionario.real@example.cl',
        'telefono' => '900000003',
        'run' => '3-5',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    $this->get(route('preview.notifications-v2.turno-asignado'))
        ->assertSuccessful()
        ->assertSee('Semana del 20-04-2026 al 24-04-2026')
        ->assertSee('JUEZ REAL')
        ->assertSee('JEFE REAL')
        ->assertSee('FUNCIONARIO REAL UNO');
});

test('turno preview normalizes stored week prefix to avoid duplicated text', function () {
    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'TM1' => 'JUEZ REAL',
        'TJ1' => 'JEFE REAL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
        'FECHA' => 'Semana del 20-04-2026 al 24-04-2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Funcionario::query()->create([
        'nombre' => 'JUEZ REAL',
        'rol' => 'juez',
        'email' => 'juez.real@example.cl',
        'telefono' => '900000001',
        'run' => '1-9',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'JEFE REAL',
        'rol' => 'jefatura',
        'email' => 'jefe.real@example.cl',
        'telefono' => '900000002',
        'run' => '2-7',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    $this->get(route('preview.notifications-v2.turno-asignado'))
        ->assertSuccessful()
        ->assertSee('Semana del 20-04-2026 al 24-04-2026')
        ->assertDontSee('Semana del Semana del');
});

test('turno preview supports date query when weekly text is empty', function () {
    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'TM1' => 'JUEZ REAL',
        'TJ1' => 'JEFE REAL',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
        'FECHA' => '',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Funcionario::query()->create([
        'nombre' => 'JUEZ REAL',
        'rol' => 'juez',
        'email' => 'juez.real@example.cl',
        'telefono' => '900000001',
        'run' => '1-9',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'JEFE REAL',
        'rol' => 'jefatura',
        'email' => 'jefe.real@example.cl',
        'telefono' => '900000002',
        'run' => '2-7',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    $this->get(route('preview.notifications-v2.turno-asignado', ['date' => '2026-04-16']))
        ->assertSuccessful()
        ->assertSee('Semana del 13-04-2026 al 17-04-2026');
});

test('ausentismo outlook preview route renders with no absences message', function () {
    $this->get(route('preview.notifications-v2.ausentismo-resumen', ['empty' => 1]))
        ->assertSuccessful()
        ->assertSee('Resumen Diario de Ausentismo')
        ->assertSee('no hay ausencias registradas');
});

test('legacy user status preview route renders for suspended status', function () {
    $this->get(route('preview.notifications-v2.user-status', ['status' => 'suspended']))
        ->assertSuccessful()
        ->assertSee('4to Tribunal de Juicio Oral en lo Penal de Santiago')
        ->assertSee('Estado de Cuenta')
        ->assertSee('Tu cuenta fue suspendida');
});
