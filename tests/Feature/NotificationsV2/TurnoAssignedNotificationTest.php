<?php

use App\Livewire\TurnoPage;
use App\Models\Funcionario;
use App\Models\User;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('notifications_v2.enabled', false);

    $this->seed(RolesAndPermissionsSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);

    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        ['id' => 1, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

test('turno1 assignment updates do not create per-slot dispatches', function () {
    Funcionario::query()->create([
        'nombre' => 'Funcionario Uno',
        'rol' => 'sala_causa',
        'email' => 'funcionario.uno@example.cl',
        'fecha_cumpleanos' => '1990-01-01',
    ]);

    Livewire::test(TurnoPage::class)
        ->set('turno1.TF1', 'Funcionario Uno')
        ->call('saveTurno1')
        ->assertHasNoErrors();

    expect(NotificationDispatch::query()->count())->toBe(0);
});

test('turno assignment with external name does not create per-slot dispatches', function () {
    Livewire::test(TurnoPage::class)
        ->set('turno1.TF1', 'Nombre Externo Sin Correo')
        ->call('saveTurno1')
        ->assertHasNoErrors();

    expect(NotificationDispatch::query()->count())->toBe(0);
});

test('turno1 cargo-only updates do not create assignment dispatches', function () {
    Livewire::test(TurnoPage::class)
        ->set('turno1.TF1_CARGO', 'Encargado de Acta')
        ->call('saveTurno1')
        ->assertHasNoErrors();

    expect(NotificationDispatch::query()->count())->toBe(0);
});

test('turno1 save normalizes week text before persisting', function () {
    Livewire::test(TurnoPage::class)
        ->set('semanaTurno', 'Semana del 20-04-2026 al 24-04-2026')
        ->call('saveTurno1')
        ->assertHasNoErrors();

    expect((string) DB::table('turno')->where('id', 2)->value('FECHA'))
        ->toBe('20-04-2026 al 24-04-2026');
});
