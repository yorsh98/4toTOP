<?php

use App\Livewire\FuncionariosPage;
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

test('it restores legacy soft deleted funcionario with same email on create', function () {
    $funcionario = Funcionario::create([
        'nombre' => 'Funcionario Antiguo',
        'run' => '11.111.111-1',
        'rol' => 'jefatura',
        'email' => 'legacy@example.cl',
        'telefono' => '999999999',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    $funcionario->delete();

    Livewire::test(FuncionariosPage::class)
        ->set('nombre', 'Funcionario Restaurado')
        ->set('run', '22.222.222-2')
        ->set('rol', 'informatico')
        ->set('email', 'legacy@example.cl')
        ->set('telefono', '988888888')
        ->set('fechaCumpleanos', '1990-02-02')
        ->call('createFuncionario')
        ->assertHasNoErrors();

    $restaurado = Funcionario::query()->where('email', 'legacy@example.cl')->first();

    expect($restaurado)->not->toBeNull()
        ->and($restaurado->deleted_at)->toBeNull()
        ->and($restaurado->nombre)->toBe('Funcionario Restaurado')
        ->and($restaurado->rol)->toBe('informatico');
});

test('it updates funcionario including email in edit mode', function () {
    $funcionario = Funcionario::create([
        'nombre' => 'Funcionario Uno',
        'run' => null,
        'rol' => 'sala_causa',
        'email' => 'funcionario1@example.cl',
        'telefono' => null,
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Livewire::test(FuncionariosPage::class)
        ->call('editFuncionario', $funcionario->id)
        ->set('email', 'funcionario.modificado@example.cl')
        ->set('nombre', 'Funcionario Editado')
        ->call('updateFuncionario')
        ->assertHasNoErrors();

    $actualizado = Funcionario::query()->findOrFail($funcionario->id);

    expect($actualizado->email)->toBe('funcionario.modificado@example.cl')
        ->and($actualizado->nombre)->toBe('Funcionario Editado');
});

test('it frees original email on delete by mutating deleted record email', function () {
    $funcionario = Funcionario::create([
        'nombre' => 'Funcionario Borrado',
        'run' => null,
        'rol' => 'ttypp',
        'email' => 'liberar@example.cl',
        'telefono' => null,
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Livewire::test(FuncionariosPage::class)
        ->call('deleteFuncionario', $funcionario->id)
        ->assertHasNoErrors();

    $eliminado = Funcionario::withTrashed()->findOrFail($funcionario->id);

    expect($eliminado->deleted_at)->not->toBeNull()
        ->and($eliminado->email)->not->toBe('liberar@example.cl')
        ->and($eliminado->email)->toContain('__deleted_');

    Funcionario::create([
        'nombre' => 'Nuevo Funcionario',
        'run' => null,
        'rol' => 'ttypp',
        'email' => 'liberar@example.cl',
        'telefono' => null,
        'fecha_cumpleanos' => '1995-03-03',
    ]);

    expect(Funcionario::query()->where('email', 'liberar@example.cl')->count())->toBe(1);
});
