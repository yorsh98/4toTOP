<?php

use App\Livewire\LibertadTabla;
use App\Livewire\OficioTabla;
use App\Models\Funcionario;
use App\Models\Libertad;
use App\Models\Oficio;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Livewire;

test('public tables start with the current year and allow selecting another year', function (string $component, string $model) {
    $current = now()->year;
    $previous = $current - 1;

    $model::create([
        'Numentregado' => 1,
        'año' => $current,
        'CausaAsig' => 'ACTUAL',
        'UserSolicitante' => 'Solicitante actual',
        'UserDirigido' => 'Motivo actual',
    ]);

    $model::create([
        'Numentregado' => 1,
        'año' => $previous,
        'CausaAsig' => 'ANTERIOR',
        'UserSolicitante' => 'Solicitante anterior',
        'UserDirigido' => 'Motivo anterior',
    ]);

    Livewire::test($component, ['modo' => 'vista'])
        ->assertSet('filtroAnio', (string) $current)
        ->assertSee('ACTUAL')
        ->assertDontSee('ANTERIOR')
        ->set('filtroAnio', (string) $previous)
        ->assertSee('ANTERIOR')
        ->assertDontSee('ACTUAL');
})->with([
    'oficios' => [OficioTabla::class, Oficio::class],
    'libertades' => [LibertadTabla::class, Libertad::class],
]);

test('creation uses the configured solicitantes catalog', function (string $component, string $model) {
    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);

    $solicitante = Funcionario::create([
        'nombre' => 'Solicitante Catalogo',
        'rol' => 'sala_causa',
        'email' => 'solicitante@example.cl',
        'fecha_cumpleanos' => '1990-01-01',
    ]);

    Funcionario::create([
        'nombre' => 'Juez Fuera de Catalogo',
        'rol' => 'juez',
        'email' => 'juez@example.cl',
        'fecha_cumpleanos' => '1990-01-01',
    ]);

    Livewire::test($component)
        ->assertSet('solicitantes', [$solicitante->nombre])
        ->set('newCausaAsig', 'CAUSA-1')
        ->set('newUserSolicitante', $solicitante->nombre)
        ->set('newUserDirigido', 'Motivo')
        ->call('create')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');

    expect($model::query()->value('UserSolicitante'))->toBe($solicitante->nombre);
})->with([
    'oficios' => [OficioTabla::class, Oficio::class],
    'libertades' => [LibertadTabla::class, Libertad::class],
]);
