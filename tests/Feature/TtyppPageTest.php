<?php

use App\Livewire\TtyppPage;
use App\Models\Funcionario;
use App\Models\TtyppRegistro;
use App\Models\User;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('superadmin can create ttypp record with nullable fields', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('rit', '123-2026')
        ->set('ruc', null)
        ->set('tipo_audiencia', null)
        ->set('numero_dias', null)
        ->set('cantidad', null)
        ->set('modalidad', null)
        ->set('encargada_ti_mp', null)
        ->set('encargada_ti_d', null)
        ->set('encargada_zoom', null)
        ->set('acta', null)
        ->set('sala', null)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ttypp_registros', [
        'rit' => '123-2026',
        'ruc' => null,
    ]);
});

test('search filters ttypp list by ruc', function () {
    TtyppRegistro::query()->create([
        'rit' => '111-2026',
        'ruc' => 'RUC-UNO',
    ]);

    TtyppRegistro::query()->create([
        'rit' => '222-2026',
        'ruc' => 'RUC-DOS',
    ]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('search', 'RUC-DOS')
        ->assertSee('222-2026')
        ->assertDontSee('111-2026');
});

test('consulta without ttypp importar permission cannot import csv', function () {
    $user = User::factory()->create();
    $user->assignRole('Consulta');

    $file = UploadedFile::fake()->createWithContent('ttypp.csv', "rit,ruc\n100-2026,RUC-100\n");

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importCsv', $file)
        ->call('importFromCsv')
        ->assertForbidden();
});

test('ttypp welcome module route is accessible', function () {
    $this->get(route('ttypp.welcome'))
        ->assertSuccessful()
        ->assertSee('TTyPP');
});

test('ttypp mail preview route renders listado for selected date', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    TtyppRegistro::query()->create([
        'rit' => '777-2026',
        'fecha_asignacion' => '2026-05-30',
    ]);

    $this->actingAs($user)
        ->get(route('preview.notifications-v2.ttypp-listado', ['fecha' => '2026-05-30']))
        ->assertSuccessful()
        ->assertSee('777-2026')
        ->assertSee('Listado TTyPP');
});

test('ttypp mail is sent to funcionarios with ttypp role', function () {
    config(['notifications_v2.enabled' => true]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Funcionario::query()->create([
        'nombre' => 'Funcionario TTyPP',
        'run' => null,
        'rol' => 'ttypp',
        'email' => 'ttypp@example.cl',
        'telefono' => null,
        'fecha_cumpleanos' => '1990-01-01',
    ]);

    TtyppRegistro::query()->create([
        'rit' => '888-2026',
        'fecha_asignacion' => now('America/Santiago')->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->call('sendTtyppMail')
        ->assertHasNoErrors();

    $dispatch = NotificationDispatch::query()
        ->where('event_key', 'like', 'ttypp.listado.diario.%')
        ->with('recipients')
        ->latest('id')
        ->first();

    expect($dispatch)->not->toBeNull()
        ->and($dispatch->recipients)->toHaveCount(1)
        ->and($dispatch->recipients->first()->email)->toBe('ttypp@example.cl');
});
