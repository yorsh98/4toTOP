<?php

use App\Models\Ausentismo;
use App\Models\Funcionario;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationRecipient;

beforeEach(function () {
    config()->set('notifications_v2.enabled', false);
});

test('daily ausentismo command excludes juez recipients from catalog delivery', function () {
    Funcionario::query()->create([
        'nombre' => 'Funcionario Uno',
        'rol' => 'juez',
        'email' => 'uno@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'Funcionario Dos',
        'rol' => 'sala_causa',
        'email' => 'dos@example.cl',
        'fecha_cumpleanos' => '1981-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'Funcionario Tres',
        'rol' => 'jefatura',
        'email' => 'tres@example.cl',
        'fecha_cumpleanos' => '1982-01-01',
    ]);

    Ausentismo::query()->create([
        'funcionario_nombre' => 'Funcionario Uno',
        'cargo' => 'Juez/a',
        'tipo_permiso' => 'Licencia Medica',
        'fecha_inicio' => '2026-04-20',
        'fecha_termino' => '2026-04-22',
        'observacion' => 'Control',
    ]);

    $this->artisan('notificaciones:ausentismo-diario', ['--date' => '2026-04-21'])
        ->assertSuccessful();

    $dispatch = NotificationDispatch::query()->latest('id')->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->event_key)->toBe('ausentismo.resumen_diario.2026-04-21');
    expect($dispatch->status)->toBe('queued');
    expect($dispatch->payload)->toMatchArray([
        'fecha_reporte' => '2026-04-21',
        'total_ausentes' => 1,
    ]);

    expect(NotificationRecipient::query()->where('dispatch_id', $dispatch->id)->count())->toBe(2);

    $this->assertDatabaseMissing('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'uno@example.cl',
    ]);

    $this->assertDatabaseHas('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'dos@example.cl',
        'status' => 'pending',
    ]);

    $this->assertDatabaseHas('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'tres@example.cl',
        'status' => 'pending',
    ]);
});

test('daily ausentismo command sends control message when there are no absences', function () {
    Funcionario::query()->create([
        'nombre' => 'Funcionario Uno',
        'rol' => 'juez',
        'email' => 'uno@example.cl',
        'fecha_cumpleanos' => '1980-01-01',
    ]);

    Funcionario::query()->create([
        'nombre' => 'Funcionario Tres',
        'rol' => 'jefatura',
        'email' => 'tres@example.cl',
        'fecha_cumpleanos' => '1982-01-01',
    ]);

    $this->artisan('notificaciones:ausentismo-diario', ['--date' => '2026-04-21'])
        ->assertSuccessful();

    $dispatch = NotificationDispatch::query()->latest('id')->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->status)->toBe('queued');
    expect($dispatch->payload)->toMatchArray([
        'fecha_reporte' => '2026-04-21',
        'total_ausentes' => 0,
        'mensaje_sin_novedades' => 'no hay ausencias registradas',
    ]);

    $this->assertDatabaseMissing('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'uno@example.cl',
    ]);

    $this->assertDatabaseHas('notification_recipients', [
        'dispatch_id' => $dispatch->id,
        'email' => 'tres@example.cl',
        'status' => 'pending',
    ]);
});

test('daily ausentismo command uses observacion when tipo permiso is otro', function () {
    Funcionario::query()->create([
        'nombre' => 'Jefatura Mail',
        'rol' => 'jefatura',
        'email' => 'jefatura@example.cl',
        'fecha_cumpleanos' => '1982-01-01',
    ]);

    Ausentismo::query()->create([
        'funcionario_nombre' => 'Funcionario Con Observacion',
        'cargo' => 'Administrativo/a',
        'tipo_permiso' => 'Otro',
        'fecha_inicio' => '2026-04-20',
        'fecha_termino' => '2026-04-22',
        'observacion' => 'Permiso especial por diligencia externa',
    ]);

    Ausentismo::query()->create([
        'funcionario_nombre' => 'Funcionario Sin Observacion',
        'cargo' => 'Administrativo/a',
        'tipo_permiso' => 'Otro',
        'fecha_inicio' => '2026-04-20',
        'fecha_termino' => '2026-04-22',
        'observacion' => '   ',
    ]);

    $this->artisan('notificaciones:ausentismo-diario', ['--date' => '2026-04-21'])
        ->assertSuccessful();

    $dispatch = NotificationDispatch::query()->latest('id')->first();

    expect($dispatch)->not->toBeNull();
    expect($dispatch->payload['ausentes_html'])->toContain('Permiso especial por diligencia externa');
    expect($dispatch->payload['ausentes_html'])->toContain('>Otro<');
    expect($dispatch->payload['ausentes_text'])->toContain('Permiso especial por diligencia externa');
    expect($dispatch->payload['ausentes_text'])->toContain(' | Otro');
});
