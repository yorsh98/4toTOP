<?php

use App\Models\Funcionario;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('notifications_v2.enabled', false);

    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
        'ACDM1' => 'MAGISTRADO UNO',
        'ACDM2' => 'MAGISTRADO DOS',
        'ACDM3' => 'MAGISTRADO TRES',
        'ACDJ1' => 'JEFATURA ACD',
        'ACDF1' => 'FUNCIONARIO ACD',
        'ACDF1_CARGO' => 'Encargado de Acta ACD',
        'FECHA' => 'Semana del 13-04-2026 al 17-04-2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Funcionario::query()->create([
        'nombre' => 'MAGISTRADO UNO',
        'run' => '11-1',
        'rol' => 'juez',
        'email' => 'mag1@example.cl',
        'telefono' => '911111111',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'JEFATURA ACD',
        'run' => '22-2',
        'rol' => 'jefatura',
        'email' => 'jefatura-acd@example.cl',
        'telefono' => '922222222',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'FUNCIONARIO ACD',
        'run' => '33-3',
        'rol' => 'sala_causa',
        'email' => 'func-acd@example.cl',
        'telefono' => '933333333',
        'fecha_cumpleanos' => '1980-01-01',
    ]);
});

test('weekly turno2 acd command creates v2 dispatch with three magistrados section', function () {
    $this->artisan('notificaciones:turno2-acd-periodo', ['--date' => '2026-04-16'])
        ->assertExitCode(0);

    $dispatch = NotificationDispatch::query()
        ->where('event_key', 'turno2.acd.resumen_semanal.2026-04-13')
        ->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->status)->toBe('queued');
    expect((string) ($dispatch->payload['magistrados_rows_html'] ?? ''))->toContain('MAGISTRADO UNO');
    expect((string) ($dispatch->payload['magistrados_rows_html'] ?? ''))->toContain('MAGISTRADO DOS');
    expect((string) ($dispatch->payload['magistrados_rows_html'] ?? ''))->toContain('MAGISTRADO TRES');
    expect((string) ($dispatch->payload['funcionarios_rows_html'] ?? ''))->toContain('Encargado de Acta ACD');

    $this->assertDatabaseHas('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'mag1@example.cl',
    ]);
});
