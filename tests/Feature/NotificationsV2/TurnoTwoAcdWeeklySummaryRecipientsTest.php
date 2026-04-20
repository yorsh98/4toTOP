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
        'email' => 'actor.turno2@example.cl',
        'name' => 'Actor Turno 2',
    ]);
    $user->assignRole('SuperAdmin');
    $this->actingAs($user);

    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
        'ACDM1' => 'MAGISTRADO ACD',
        'ACDM2' => 'MAGISTRADO SIN CORREO',
        'ACDM3' => 'MAGISTRADO ACD 3',
        'ACDJ1' => 'JEFATURA ACD',
        'ACDF1' => 'FUNCIONARIO ACD',
        'FECHA' => 'Semana del 20-04-2026 al 24-04-2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Funcionario::query()->create([
        'nombre' => 'MAGISTRADO ACD',
        'rol' => 'juez',
        'email' => 'magistrado.acd@example.cl',
        'telefono' => '900100001',
        'run' => '1-9',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'MAGISTRADO ACD 3',
        'rol' => 'juez',
        'email' => 'magistrado.acd3@example.cl',
        'telefono' => '900100003',
        'run' => '3-7',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'JEFATURA ACD',
        'rol' => 'jefatura',
        'email' => 'jefatura.acd@example.cl',
        'telefono' => '900200002',
        'run' => '2-7',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'FUNCIONARIO ACD',
        'rol' => 'sala_causa',
        'email' => 'funcionario.acd@example.cl',
        'telefono' => '900300003',
        'run' => '3-5',
        'fecha_cumpleanos' => '1980-01-01',
    ]);
});

test('weekly turno2 acd summary sends all funcionarios except jueces and selected magistrados', function () {
    Livewire::test(TurnoPage::class)
        ->call('sendTurno2AcdSummary')
        ->assertHasNoErrors();

    $dispatch = NotificationDispatch::query()
        ->where('event_key', 'like', 'turno2.acd.resumen_semanal.%')
        ->latest('id')
        ->first();

    expect($dispatch)->not->toBeNull();

    $emails = $dispatch->recipients()
        ->pluck('email')
        ->map(fn (string $email) => strtolower($email))
        ->values()
        ->all();

    expect($emails)->toContain('magistrado.acd@example.cl');
    expect($emails)->toContain('magistrado.acd3@example.cl');
    expect($emails)->toContain('jefatura.acd@example.cl');
    expect($emails)->toContain('funcionario.acd@example.cl');
    expect($emails)->not->toContain('actor.turno2@example.cl');
    expect(count($emails))->toBe(4);
});
