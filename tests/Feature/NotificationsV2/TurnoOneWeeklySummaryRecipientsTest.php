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

    $user = User::factory()->create([
        'email' => 'actor.turno@example.cl',
        'name' => 'Actor Turno',
    ]);
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);

    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'TM1' => 'JUEZ REAL',
        'TJ1' => 'JEFE REAL',
        'TF1' => 'FUNCIONARIO REAL UNO',
        'TF2' => 'FUNCIONARIO SIN CORREO',
        'FECHA' => 'Semana del 20-04-2026 al 24-04-2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
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
});

test('weekly turno summary sends all funcionarios except jueces and only selected magistrados', function () {
    Livewire::test(TurnoPage::class)
        ->call('sendTurno1Summary')
        ->assertHasNoErrors();

    $dispatch = NotificationDispatch::query()
        ->where('event_key', 'like', 'turno1.resumen_semanal.%')
        ->latest('id')
        ->first();

    expect($dispatch)->not->toBeNull();

    $emails = $dispatch->recipients()
        ->pluck('email')
        ->map(fn (string $email) => strtolower($email))
        ->values()
        ->all();

    expect($emails)->toContain('juez.real@example.cl');
    expect($emails)->toContain('jefe.real@example.cl');
    expect($emails)->toContain('funcionario.real@example.cl');
    expect($emails)->not->toContain('actor.turno@example.cl');
    expect($emails)->not->toContain('funcionario.sin.correo@example.cl');
    expect(count($emails))->toBe(3);
});
