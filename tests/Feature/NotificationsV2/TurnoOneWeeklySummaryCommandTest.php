<?php

use App\Models\Funcionario;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config()->set('notifications_v2.enabled', false);

    DB::table('turno')->truncate();
    DB::table('turno')->insert([
        'id' => 1,
        'TM1' => 'TERESA DE LOURDES ZURITA TORO',
        'TJ1' => 'TERESA DE LOURDES ZURITA TORO',
        'TF1' => 'NICOLE ALEJANDRA DIAZ GARCIA',
        'TF1_CARGO' => 'Encargado de Acta',
        'TF2' => 'BELEN VALENCIA ESPINOZA',
        'TF2_CARGO' => 'Atencion de Publico',
        'TF3' => 'JORGE ANDRES TRONCOSO REYES',
        'TF3_CARGO' => 'Funcion de equipo',
        'FECHA' => 'Semana del 13-04-2026 al 17-04-2026',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('turno')->insert([
        'id' => 2,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Funcionario::query()->create([
        'nombre' => 'TERESA DE LOURDES ZURITA TORO',
        'run' => '8.713.736-8',
        'rol' => 'juez',
        'email' => 'tzurita@pjud.cl',
        'telefono' => '990519412',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'NICOLE ALEJANDRA DIAZ GARCIA',
        'run' => '18.968.502-5',
        'rol' => 'sala_causa',
        'email' => 'ndiazg@pjud.cl',
        'telefono' => '950067717',
        'fecha_cumpleanos' => '1980-01-01',
    ]);
});

test('weekly turno1 command creates v2 dispatch with full table payload', function () {
    $this->artisan('notificaciones:turno1-semanal', ['--date' => '2026-04-16'])
        ->assertExitCode(0);

    $dispatch = NotificationDispatch::query()->where('event_key', 'turno1.resumen_semanal.2026-04-13')->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->status)->toBe('queued');
    expect($dispatch->payload)->toMatchArray([
        'tribunal_oral' => '4to TOP',
        'juez_despacho_run' => '8.713.736-8',
        'juez_despacho_nombre' => 'TERESA DE LOURDES ZURITA TORO',
        'jefe_turno_observaciones' => 'Jefe de Turno',
    ]);
    expect((string) ($dispatch->payload['funcionarios_rows_html'] ?? ''))->toContain('NICOLE ALEJANDRA DIAZ GARCIA');

    $this->assertDatabaseHas('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'tzurita@pjud.cl',
    ]);
});
