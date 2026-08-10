<?php

use App\Livewire\TtyppPage;
use App\Models\Audiencia;
use App\Models\Funcionario;
use App\Models\TtyppRegistro;
use App\Models\User;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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
        'fecha_asignacion' => '2026-06-10',
    ]);

    TtyppRegistro::query()->create([
        'rit' => '222-2026',
        'ruc' => 'RUC-DOS',
        'fecha_asignacion' => '2026-06-10',
    ]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('selectedDate', '2026-06-10')
        ->set('search', 'RUC-DOS')
        ->assertSee('222-2026')
        ->assertDontSee('111-2026');
});

test('ttypp list filter initializes with next day', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-09 10:00:00', 'America/Santiago'));

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->assertSet('selectedDate', '2026-06-10')
        ->assertSet('fecha_asignacion', '2026-06-10');

    Carbon::setTestNow();
});

test('consulta without ttypp importar permission cannot import excel', function () {
    $user = User::factory()->create();
    $user->assignRole('Consulta');

    $file = makeSiagjXlsxUpload([
        ['168', '2026', 'Ordinaria', 'Audiencia.', 'Audiencia de juicio oral TOP', '10/06/2026 09:00', 'Por realizarse.'],
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importExcel', $file)
        ->call('importFromExcel')
        ->assertForbidden();
});

test('superadmin can import siagj excel filtered rows into ttypp', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $file = makeSiagjXlsxUpload([
        ['168', '2026', 'Ordinaria', 'Audiencia.', 'Audiencia de juicio oral TOP', '10/06/2026 09:00', 'Por realizarse.'],
        ['999', '2026', 'Ordinaria', 'Audiencia.', 'Otro evento', '10/06/2026 09:00', 'Por realizarse.'],
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importExcel', $file)
        ->call('importFromExcel')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ttypp_registros', [
        'rit' => '168-2026',
        'fecha_asignacion' => '2026-06-10',
    ]);

    $this->assertDatabaseMissing('ttypp_registros', [
        'rit' => '999-2026',
    ]);
});

test('superadmin can import siagj xls filtered rows into ttypp', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $file = makeSiagjXlsUpload([
        ['169', '2026', 'Ordinaria', 'Audiencia.', 'Audiencia de juicio oral TOP', '10/06/2026 09:00', 'Por realizarse.'],
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importExcel', $file)
        ->call('importFromExcel')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ttypp_registros', [
        'rit' => '169-2026',
        'fecha_asignacion' => '2026-06-10',
    ]);
});

test('superadmin imports native excel date as day month year from siagj xlsx', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    $file = makeSiagjXlsxUpload([
        ['170', '2026', 'Ordinaria', 'Audiencia.', 'Audiencia de juicio oral TOP', ExcelDate::PHPToExcel(new DateTimeImmutable('2026-06-10 09:00:00')), 'Por realizarse.'],
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importExcel', $file)
        ->call('importFromExcel')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ttypp_registros', [
        'rit' => '170-2026',
        'fecha_asignacion' => '2026-06-10',
    ]);
});

test('superadmin import fills ruc from audiencia by generated rit', function () {
    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Audiencia::query()->create([
        'fecha' => '2026-06-10',
        'rit' => '168-2026',
        'sala' => 'Sala 1',
        'hora_inicio' => '09:00:00',
        'ruc' => '2600012345-6',
        'cta_zoom' => 'Cuenta TOP',
        'tipo_audiencia' => 'Audiencia de juicio oral TOP',
        'encargado_causa' => 'Encargado Causa',
        'acta' => 'Acta Uno',
        'acusados' => [['nombre_completo' => 'Acusado Uno', 'situacion' => 'Libre']],
        'estado' => 'POR_REALIZARSE',
        'JuezR' => 'Juez Redactor',
    ]);

    $file = makeSiagjXlsxUpload([
        ['168', '2026', 'Ordinaria', 'Audiencia.', 'Audiencia de juicio oral TOP', ExcelDate::PHPToExcel(new DateTimeImmutable('2026-06-10 09:00:00')), 'Por realizarse.'],
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('importExcel', $file)
        ->call('importFromExcel')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('ttypp_registros', [
        'rit' => '168-2026',
        'ruc' => '2600012345-6',
        'fecha_asignacion' => '2026-06-10',
    ]);

    // tipo_audiencia, acta, sala, jueces should NOT be filled at import time
    $registro = TtyppRegistro::query()->where('rit', '168-2026')->first();
    expect($registro->tipo_audiencia)->toBeNull()
        ->and($registro->acta)->toBeNull()
        ->and($registro->sala)->toBeNull()
        ->and($registro->jueces)->toBeNull();
});

test('superadmin exports filtered ttypp list as xlsx', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-09 10:00:00', 'America/Santiago'));

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    TtyppRegistro::query()->create([
        'rit' => '168-2026',
        'ruc' => '2600012345-6',
        'fecha_asignacion' => '2026-06-10',
    ]);

    $component = Livewire::actingAs($user)
        ->test(TtyppPage::class);

    $component
        ->set('selectedDate', '2026-06-10');

    $component
        ->call('exportExcel')
        ->assertRedirect(route('ttypp.export', [
            'fecha' => '2026-06-10',
            'search' => '',
        ], absolute: false));

    $this->actingAs($user)
        ->get(route('ttypp.export', ['fecha' => '2026-06-10']))
        ->assertDownload('ttypp_20260609_100000.xlsx');

    Carbon::setTestNow();
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
        'fecha_asignacion' => '2026-06-10',
    ]);

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('selectedDate', '2026-06-10')
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

function makeSiagjXlsxUpload(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['RIT', 'AÑOCAUSA', 'TIPOCAUSA', 'TIPO_TRAMITE', 'EVENTO', 'FECHAEVENTO', 'ESTADOEVENTO'],
        ...$rows,
    ]);

    $path = tempnam(sys_get_temp_dir(), 'ttypp-siagj-').'.xlsx';
    (new Xlsx($spreadsheet))->save($path);

    $content = file_get_contents($path);
    @unlink($path);

    return UploadedFile::fake()->createWithContent('ttypp salida SIAGJ.xlsx', $content ?: '');
}

function makeSiagjXlsUpload(array $rows): UploadedFile
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([
        ['RIT', 'AÑOCAUSA', 'TIPOCAUSA', 'TIPO_TRAMITE', 'EVENTO', 'FECHAEVENTO', 'ESTADOEVENTO'],
        ...$rows,
    ]);

    $path = tempnam(sys_get_temp_dir(), 'ttypp-siagj-').'.xls';
    (new Xls($spreadsheet))->save($path);

    $content = file_get_contents($path);
    @unlink($path);

    return UploadedFile::fake()->createWithContent('ttypp salida SIAGJ.xls', $content ?: '');
}

test('form rit update autocompletes only ruc from audiencia', function () {
    Audiencia::query()->create([
        'fecha' => '2026-06-10',
        'rit' => '168-2026',
        'sala' => 'Sala 2',
        'hora_inicio' => '09:00:00',
        'ruc' => '2600012345-6',
        'cta_zoom' => 'Cuenta TOP',
        'tipo_audiencia' => 'Audiencia de juicio oral TOP',
        'encargado_causa' => 'Encargado Causa',
        'acta' => 'Acta Dos',
        'acusados' => [['nombre_completo' => 'Acusado Uno', 'situacion' => 'Libre']],
        'estado' => 'POR_REALIZARSE',
        'JuezP' => 'Magistrado Uno',
        'JuezR' => 'Magistrado Dos',
        'JuezI' => 'Magistrado Tres',
    ]);

    $user = User::factory()->create();
    $user->assignRole('SuperAdmin');

    Livewire::actingAs($user)
        ->test(TtyppPage::class)
        ->set('rit', '168-2026')
        ->assertSet('ruc', '2600012345-6')
        ->assertSet('tipo_audiencia', null)
        ->assertSet('acta', null)
        ->assertSet('sala', null)
        ->assertSet('jueces', null);
});

test('daily ttypp command enriches records from audiencias before sending', function () {
    config(['notifications_v2.enabled' => true]);

    Funcionario::query()->create([
        'nombre' => 'Funcionario TTyPP',
        'run' => null,
        'rol' => 'ttypp',
        'email' => 'ttypp@example.cl',
        'telefono' => null,
        'fecha_cumpleanos' => '1990-01-01',
    ]);

    TtyppRegistro::query()->create([
        'rit' => '168-2026',
        'ruc' => '2600012345-6',
        'fecha_asignacion' => '2026-06-10',
    ]);

    Audiencia::query()->create([
        'fecha' => '2026-06-10',
        'rit' => '168-2026',
        'sala' => 'Sala 3',
        'hora_inicio' => '09:00:00',
        'ruc' => '2600012345-6',
        'cta_zoom' => 'Cuenta TOP',
        'tipo_audiencia' => 'Audiencia de juicio oral TOP',
        'encargado_causa' => 'Encargado Causa',
        'acta' => 'Acta Tres',
        'acusados' => [['nombre_completo' => 'Acusado Uno', 'situacion' => 'Libre']],
        'estado' => 'POR_REALIZARSE',
        'JuezP' => 'Juez P',
        'JuezR' => 'Juez R',
    ]);

    $this->artisan('notificaciones:ttypp-diario', ['fecha' => '2026-06-10'])
        ->assertSuccessful();

    $registro = TtyppRegistro::query()->where('rit', '168-2026')->first();

    expect($registro->tipo_audiencia)->toBe('Audiencia de juicio oral TOP')
        ->and($registro->acta)->toBe('Acta Tres')
        ->and($registro->sala)->toBe('Sala 3')
        ->and($registro->jueces)->toBe('Juez P, Juez R');
});
