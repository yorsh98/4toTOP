<?php

use App\Models\Funcionario;
use App\Models\Turno;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);

    $this->turno1 = Turno::create([]);
    $this->turno2 = Turno::create([]);
});

test('it allows free text assignment for magistrado funcionario and jefatura fields', function () {
    Funcionario::create([
        'nombre' => 'Funcionario TI',
        'rol' => 'informatico',
        'email' => 'ti@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Juez Permitido',
        'rol' => 'juez',
        'email' => 'juez@example.cl',
        'fecha_cumpleanos' => '1981-02-02',
    ]);

    $response = $this->patch(route('turno.update', $this->turno1->id), [
        'TM1' => 'Funcionario TI',
        'TF1' => 'Nombre Externo Funcionario',
        'TJ1' => 'Nombre Externo Jefatura',
    ]);

    $response->assertRedirect(route('turno'));

    $turno = Turno::findOrFail($this->turno1->id);

    expect($turno->TM1)->toBe('Funcionario TI')
        ->and($turno->TF1)->toBe('Nombre Externo Funcionario')
        ->and($turno->TJ1)->toBe('Nombre Externo Jefatura');
});

test('it accepts valid role assignments for turno 1 fields', function () {
    Funcionario::create([
        'nombre' => 'Juez Uno',
        'rol' => 'juez',
        'email' => 'juez1@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Funcionario Uno',
        'rol' => 'sala_causa',
        'email' => 'func1@example.cl',
        'fecha_cumpleanos' => '1981-02-02',
    ]);

    Funcionario::create([
        'nombre' => 'Jefatura Uno',
        'rol' => 'jefatura',
        'email' => 'jef1@example.cl',
        'fecha_cumpleanos' => '1982-03-03',
    ]);

    $response = $this->patch(route('turno.update', $this->turno1->id), [
        'TM1' => 'Juez Uno',
        'TF1' => 'Funcionario Uno',
        'TJ1' => 'Jefatura Uno',
    ]);

    $response->assertRedirect(route('turno'));
    $response->assertSessionHas('success');

    $turno = Turno::findOrFail($this->turno1->id);

    expect($turno->TM1)->toBe('Juez Uno')
        ->and($turno->TF1)->toBe('Funcionario Uno')
        ->and($turno->TJ1)->toBe('Jefatura Uno');
});
