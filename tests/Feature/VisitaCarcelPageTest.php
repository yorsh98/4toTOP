<?php

use App\Livewire\VisitaCarcelPage;
use App\Models\Penal;
use App\Models\User;
use App\Models\VisitaCarcelCausa;
use App\Models\VisitaCarcelImputado;
use App\Models\VisitaCarcelImputadoHistorial;
use App\Services\SafeSpreadsheetReader;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['navbar.visita_carcel.visible', 'visita_carcel.ver', 'visita_carcel.gestionar'] as $permission) {
        Permission::findOrCreate($permission, 'web');
    }
});

test('guest is redirected from the visita carcel module', function () {
    $this->get(route('visita-carcel.index'))
        ->assertRedirect(route('login'));
});

test('user with permission can access the visita carcel module', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('visita_carcel.ver');

    $this->actingAs($user)
        ->get(route('visita-carcel.index'))
        ->assertOk()
        ->assertSee('Comparacion de causas');
});

test('manager creates a cause without imputados', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->assertSee('Nueva causa')
        ->set('ruc', '2301234567-8')
        ->set('rit', '142-2026')
        ->call('guardarCausa')
        ->assertHasNoErrors();

    $causa = VisitaCarcelCausa::query()->where('ruc', '2301234567-8')->firstOrFail();

    expect($causa->rit)->toBe('142-2026')
        ->and($causa->created_by)->toBe($user->id);

    expect($causa->imputados()->count())->toBe(0);
});

test('manager can view the required alimentación spreadsheet format', function () {
    $user = visitaCarcelManager();

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('seleccionarModo', 'alimentacion')
        ->call('abrirInformacionAlimentacion')
        ->assertSee('Formato requerido para alimentar el catálogo')
        ->assertSee('ULTIMA REV. P.P.');
});

test('comparison import reports the reason for each skipped row', function () {
    $user = visitaCarcelManager();
    VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivoMovimientos', UploadedFile::fake()->createWithContent('comparacion.xlsx', visitaCarcelComparacionPlanilla([
            [142, 2026, '2301234567-8'],
            [143, 2026, '2301234567-8'],
            [142, 2026, '9999999999-9'],
            [142, 2026, '2301234567-8'],
        ])))
        ->call('importarMovimientos')
        ->assertHasNoErrors()
        ->assertSet('resultadoImportacion.movimientos_creados', 1)
        ->assertSet('resultadoImportacion.inconsistencias', 1)
        ->assertSet('resultadoImportacion.causas_no_encontradas', 1)
        ->assertSet('resultadoImportacion.duplicados', 1)
        ->assertSet('resultadoImportacion.errores.0', 'Fila 3: el RIT 143-2026 no coincide con el catálogo.');
});

test('comparison import shows a file error instead of failing when the reader rejects it', function () {
    $user = visitaCarcelManager();
    $reader = Mockery::mock(SafeSpreadsheetReader::class);
    $reader->shouldReceive('load')->once()->andThrow(new RuntimeException('El contenido del archivo no coincide con el formato permitido.'));
    app()->instance(SafeSpreadsheetReader::class, $reader);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivoMovimientos', UploadedFile::fake()->createWithContent('comparacion.xlsx', visitaCarcelComparacionPlanilla([])))
        ->call('importarMovimientos')
        ->assertHasErrors(['archivoMovimientos' => 'El contenido del archivo no coincide con el formato permitido.']);
});

test('causes are paginated and searching returns to the first page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('visita_carcel.ver');

    foreach (range(1, 21) as $number) {
        VisitaCarcelCausa::query()->create([
            'ruc' => sprintf('230123%04d-8', $number),
            'rit' => $number.'-2026',
        ])->imputados()->create([
            'nombre' => "Imputado {$number}",
            'rut' => "12.345.{$number}-9",
            'penal' => 'Santiago 1',
            'delito' => 'Robo',
            'fecha_ingreso_pp' => '2026-01-01',
        ]);
    }

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->assertSee('Total: 21')
        ->call('setPage', 2)
        ->assertSee('2301230011-8')
        ->set('search', '2301230001-8')
        ->assertSee('2301230001-8');
});

test('manager adds an imputado from the cause record', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('verFicha', $causa->id)
        ->set('nombre', 'Maria Gonzalez Rojas')
        ->set('rut', '15.987.654-3')
        ->set('penal', 'Colina 1')
        ->set('delito', 'Homicidio')
        ->set('fechaIngresoPp', '2026-08-02')
        ->call('guardarImputado')
        ->assertHasNoErrors()
        ->assertDispatched('app-toast');

    expect(VisitaCarcelCausa::query()->count())->toBe(1)
        ->and($causa->imputados()->count())->toBe(1);
});

test('notifications show imputados from day 160 through day 180', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24', 'America/Santiago'));
    $user = visitaCarcelManager();
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);

    foreach ([160, 180, 181] as $days) {
        $causa->imputados()->create([
            'nombre' => "Imputado {$days}", 'rut' => "12.345.67{$days}-9", 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-01-01',
            'ultima_rev_pp' => now('America/Santiago')->subDays($days)->toDateString(),
        ]);
    }

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('seleccionarModo', 'notificaciones')
        ->assertSee('Imputado 160')
        ->assertSee('Imputado 180')
        ->assertDontSee('Imputado 181')
        ->set('searchNotificaciones', 'Imputado 160')
        ->assertSee('Imputado 160')
        ->assertDontSee('Imputado 180');

    Carbon::setTestNow();
});

test('manager updates the last PP review with an audit record', function () {
    $user = visitaCarcelManager();
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $imputado = $causa->imputados()->create(['nombre' => 'Juan Perez Soto', 'rut' => '12.345.678-9', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-01-01', 'ultima_rev_pp' => '2026-03-01']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('abrirFormularioRevisionPp', $imputado->id)
        ->set('ultimaRevisionPp', '2026-08-20')
        ->call('actualizarRevisionPp')
        ->assertHasNoErrors();

    expect($imputado->refresh()->ultima_rev_pp->toDateString())->toBe('2026-08-20')
        ->and(VisitaCarcelImputadoHistorial::query()->latest('id')->firstOrFail()->cambios)->toBe([
            'ultima_rev_pp' => ['old' => '2026-03-01', 'new' => '2026-08-20'],
        ]);
});

test('manager can set the PP review date to today', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-24 10:00', 'America/Santiago'));
    $user = visitaCarcelManager();

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('usarFechaActualRevisionPp')
        ->assertSet('ultimaRevisionPp', '2026-08-24');

    Carbon::setTestNow();
});

test('manager edits an imputado and records every changed field', function () {
    $user = visitaCarcelManager();
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $imputado = $causa->imputados()->create([
        'nombre' => 'Juan Perez Soto', 'rut' => '12.345.678-9', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-01-01',
        'plazo_cierre' => '2026-03-01', 'ultima_rev_pp' => '2026-03-02', 'juez' => 'Juez anterior', 'defensor' => 'Defensor anterior', 'fecha_ult_res' => '2026-03-03', 'ult_resolucion' => 'Resolución anterior',
    ]);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('abrirEdicionImputado', $imputado->id)
        ->assertSet('imputadoEdicion.nombre', 'Juan Perez Soto')
        ->set('imputadoEdicion.nombre', 'Juan Perez Actualizado')
        ->set('imputadoEdicion.ultima_rev_pp', '2026-08-20')
        ->set('imputadoEdicion.ult_resolucion', 'Resolución actualizada')
        ->call('guardarEdicionImputado')
        ->assertHasNoErrors();

    $historial = VisitaCarcelImputadoHistorial::query()->latest('id')->firstOrFail();

    expect($imputado->refresh()->nombre)->toBe('Juan Perez Actualizado')
        ->and($imputado->ultima_rev_pp->toDateString())->toBe('2026-08-20')
        ->and($historial->archivo)->toBe('Edición manual desde ficha de causa')
        ->and($historial->cambios)->toHaveKeys(['nombre', 'ultima_rev_pp', 'ult_resolucion']);
});

test('manager cannot create an active cause with a duplicate ruc', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);
    VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('ruc', '2301234567-8')
        ->set('rit', '999-2026')
        ->call('guardarCausa')
        ->assertHasErrors(['ruc']);

    expect(VisitaCarcelCausa::query()->count())->toBe(1);
});

test('manager soft deletes an imputado with traceability', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $imputado = $causa->imputados()->create(['nombre' => 'Juan Perez Soto', 'rut' => '12.345.678-9', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-08-01']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('eliminarImputado', $imputado->id)
        ->assertDispatched('app-toast');

    $trashed = VisitaCarcelImputado::withTrashed()->findOrFail($imputado->id);
    expect($trashed->trashed())->toBeTrue()->and($trashed->deleted_by)->toBe($user->id);
});

test('manager soft deletes a cause and its imputados', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $imputado = $causa->imputados()->create(['nombre' => 'Juan Perez Soto', 'rut' => '12.345.678-9', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-08-01']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('eliminarCausa', $causa->id)
        ->assertDispatched('app-toast');

    expect(VisitaCarcelCausa::withTrashed()->findOrFail($causa->id)->trashed())->toBeTrue()
        ->and(VisitaCarcelCausa::withTrashed()->findOrFail($causa->id)->deleted_by)->toBe($user->id)
        ->and(VisitaCarcelImputado::withTrashed()->findOrFail($imputado->id)->trashed())->toBeTrue()
        ->and(VisitaCarcelImputado::withTrashed()->findOrFail($imputado->id)->deleted_by)->toBe($user->id);
});

test('viewer cannot manage the visita carcel catalog', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('visita_carcel.ver');

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->call('guardarCausa')
        ->assertForbidden();
});

test('export by penal requires the active cross', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('visita_carcel.gestionar');

    $this->actingAs($user)
        ->get(route('visita-carcel.exportar-por-penal'))
        ->assertStatus(422);
});

test('manager updates multiple legacy visita carcel spreadsheets', function () {
    $user = visitaCarcelManager();
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $primero = $causa->imputados()->create(['nombre' => 'Juan Perez Soto', 'rut' => '12.345.678-9', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-08-01']);
    $segundo = $causa->imputados()->create(['nombre' => 'Ana Diaz Soto', 'rut' => '11.111.111-1', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-08-01']);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivosActualizacion', [
            UploadedFile::fake()->createWithContent('penal-uno.xls', visitaCarcelPlanilla([
                [142, 2026, '2301234567-8', '12.345.678-9', 46250, 46251, 'Juez Uno', 'Defensor Uno', 46252, 'Resolucion uno'],
            ], 'xls')),
            UploadedFile::fake()->createWithContent('penal-dos.xlsx', visitaCarcelPlanilla([
                [142, 2026, '2301234567-8', '11.111.111-1', 46253, 46254, 'Juez Dos', 'Defensor Dos', 46255, 'Resolucion dos'],
            ], 'xlsx')),
        ])
        ->call('actualizarDesdeArchivos')
        ->assertHasNoErrors();

    expect($primero->refresh()->juez)->toBe('Juez Uno')
        ->and($segundo->refresh()->juez)->toBe('Juez Dos')
        ->and(VisitaCarcelImputadoHistorial::query()->count())->toBe(2);
});

test('manager processes valid spreadsheets from a zip and reports invalid ones', function () {
    $user = visitaCarcelManager();
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $imputado = $causa->imputados()->create(['nombre' => 'Juan Perez Soto', 'rut' => '12.345.678-9', 'penal' => 'Santiago 1', 'delito' => 'Robo', 'fecha_ingreso_pp' => '2026-08-01']);
    $zip = new ZipArchive;
    $path = tempnam(sys_get_temp_dir(), 'visita-carcel-test-');
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('penal-valido.xlsx', visitaCarcelPlanilla([
        [142, 2026, '2301234567-8', '12.345.678-9', 46250, 46251, 'Juez ZIP', 'Defensor ZIP', 46252, 'Resolucion ZIP'],
    ], 'xlsx'));
    $zip->addFromString('planilla-invalida.xlsx', visitaCarcelPlanilla([[1, 2, 3]], 'xlsx'));
    $zip->addFromString('.~lock.planilla.xls#', 'auxiliar');
    $zip->close();

    try {
        Livewire::actingAs($user)
            ->test(VisitaCarcelPage::class)
            ->set('archivosActualizacion', [UploadedFile::fake()->createWithContent('planillas.zip', file_get_contents($path))])
            ->call('actualizarDesdeArchivos')
            ->assertHasNoErrors()
            ->assertSet('resultadoActualizacion.archivos_procesados', 1)
            ->assertSet('resultadoActualizacion.archivos_omitidos', 1);
    } finally {
        @unlink($path);
    }

    expect($imputado->refresh()->juez)->toBe('Juez ZIP')
        ->and(VisitaCarcelImputadoHistorial::query()->count())->toBe(1);
});

test('manager creates missing causes and imputados from a legacy spreadsheet', function () {
    $user = visitaCarcelManager();
    Penal::query()->firstOrCreate(['nombre' => 'CCP Valdivia'], ['activo' => true]);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivosActualizacion', [UploadedFile::fake()->createWithContent('valdivia.xlsx', visitaCarcelPlanilla([
            [900, 2026, '2600000000-1', '12.345.678-9', 46250, 46251, 'Juez Nuevo', 'Defensor Nuevo', 46252, 'Resolucion nueva'],
        ], 'xlsx', 'C.C.P. VALDIVIA', true))])
        ->call('actualizarDesdeArchivos')
        ->assertHasNoErrors()
        ->assertSet('resultadoActualizacion.archivos.0.error', null)
        ->assertSet('resultadoActualizacion.sin_coincidencia', 0)
        ->assertSet('resultadoActualizacion.causas_creadas', 1)
        ->assertSet('resultadoActualizacion.imputados_creados', 1);

    $causa = VisitaCarcelCausa::query()->where('ruc', '2600000000-1')->firstOrFail();
    $imputado = $causa->imputados()->firstOrFail();

    expect($causa->rit)->toBe('900-2026')
        ->and($imputado->penal)->toBe('CCP Valdivia')
        ->and($imputado->juez)->toBe('Juez Nuevo');
});

test('manager recognizes legacy names for Santiago Uno and Colina II', function () {
    $user = visitaCarcelManager();
    Penal::query()->firstOrCreate(['nombre' => 'CDP Stgo Uno'], ['activo' => true]);
    Penal::query()->firstOrCreate(['nombre' => 'CCP Colina II'], ['activo' => true]);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivosActualizacion', [
            UploadedFile::fake()->createWithContent('santiago.xls', visitaCarcelPlanilla([
                [100, 2026, '2600000001-1', '12.345.678-9', 46250, 46251, 'Juez Santiago', 'Defensor Santiago', 46252, 'Resolucion Santiago'],
            ], 'xls', 'C.D.P. SANTIAGO UNO')),
            UploadedFile::fake()->createWithContent('colina.xls', visitaCarcelPlanilla([
                [101, 2026, '2600000002-2', '11.111.111-1', 46250, 46251, 'Juez Colina', 'Defensor Colina', 46252, 'Resolucion Colina'],
            ], 'xls', 'C.C.P. DE COLINA II')),
        ])
        ->call('actualizarDesdeArchivos')
        ->assertHasNoErrors()
        ->assertSet('resultadoActualizacion.imputados_creados', 2);

    expect(VisitaCarcelCausa::query()->where('ruc', '2600000001-1')->firstOrFail()->imputados()->firstOrFail()->penal)->toBe('CDP Stgo Uno')
        ->and(VisitaCarcelCausa::query()->where('ruc', '2600000002-2')->firstOrFail()->imputados()->firstOrFail()->penal)->toBe('CCP Colina II');
});

test('manager imports every worksheet from the Santiago spreadsheet', function () {
    $user = visitaCarcelManager();

    foreach (['CDP Stgo Uno', 'CDT Pudahuel Norte', 'REPAS', 'CDP Stgo Sur', 'CPF San Miguel', 'Cuartel Gral. PDI Borgoño'] as $penal) {
        Penal::query()->firstOrCreate(['nombre' => $penal], ['activo' => true]);
    }

    $hojas = [
        ['C.D.P. SANTIAGO I', 'C.D.P. SANTIAGO UNO', '2600000010-1'],
        ['CDT PUDAHUEL NORTE', 'CDT PUDAHUEL NORTE', '2600000011-1'],
        ['REPAS', 'REPAS', '2600000012-1'],
        ['C.D.P. DE SANTIAGO SUR', 'C.D.P. DE SANTIAGO SUR', '2600000013-1'],
        ['CPF SAN MIGUEL', 'CPF SAN MIGUEL', '2600000014-1'],
        ['CUARTEL GENERAL PDI BORGOÑO', 'CUARTEL GENERAL PDI BORGOÑO', '2600000015-1'],
    ];

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivosActualizacion', [UploadedFile::fake()->createWithContent('santiago.xls', visitaCarcelPlanillaMultiple($hojas))])
        ->call('actualizarDesdeArchivos')
        ->assertHasNoErrors()
        ->assertSet('resultadoActualizacion.imputados_creados', 6)
        ->assertSet('resultadoActualizacion.causas_creadas', 6);

    foreach (array_column($hojas, 2) as $ruc) {
        expect(VisitaCarcelCausa::query()->where('ruc', $ruc)->exists())->toBeTrue();
    }
});

test('manager corrects harmless typos in an ingreso date', function () {
    $user = visitaCarcelManager();
    Penal::query()->firstOrCreate(['nombre' => 'CCP Valdivia'], ['activo' => true]);

    Livewire::actingAs($user)
        ->test(VisitaCarcelPage::class)
        ->set('archivosActualizacion', [UploadedFile::fake()->createWithContent('fecha-corregida.xls', visitaCarcelPlanilla([
            [300, 2026, '2600000016-1', '12.345.678-9', 46250, 46251, 'Juez', 'Defensor', 46252, 'Resolucion'],
        ], 'xls', 'C.C.P. VALDIVIA', false, "20/0'7/2022"))])
        ->call('actualizarDesdeArchivos')
        ->assertHasNoErrors()
        ->assertSet('resultadoActualizacion.imputados_creados', 1);

    expect(VisitaCarcelCausa::query()->where('ruc', '2600000016-1')->firstOrFail()->imputados()->firstOrFail()->fecha_ingreso_pp->toDateString())->toBe('2022-07-20');
});

function visitaCarcelManager(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);

    return $user;
}

function visitaCarcelPlanilla(array $filas, string $extension, string $penal = 'Penal', bool $columnasResiduales = false, mixed $fechaIngreso = 46200): string
{
    $planilla = new Spreadsheet;
    $formatoValido = count($filas[0] ?? []) === 10;
    $planilla->getActiveSheet()->fromArray([
        ['Lugar de Reclusion', $penal],
        $formatoValido ? ['Tribunal', 'Rit', 'Año', 'Ruc', 'Imputado', 'RUT', 'Delito', 'Fec. Ingreso P.P.', 'Plazo Cierre', 'Ultima Rev. P.P.', 'Juez', 'Defensor', 'Fec. Ult. Res.', 'Ult. Resolución'] : $filas[0],
        ...($formatoValido ? array_map(fn (array $fila) => ['4 TOP Santiago', $fila[0], $fila[1], $fila[2], 'Imputado de prueba', $fila[3], 'Delito de prueba', $fechaIngreso, $fila[4], $fila[5], $fila[6], $fila[7], $fila[8], $fila[9]], $filas) : []),
    ]);
    if ($columnasResiduales) {
        $planilla->getActiveSheet()->setCellValue('BK1', 'Formato residual');
    }
    $path = tempnam(sys_get_temp_dir(), 'visita-carcel-planilla-');
    ($extension === 'xls' ? new Xls($planilla) : new Xlsx($planilla))->save($path);

    try {
        return file_get_contents($path);
    } finally {
        @unlink($path);
    }
}

function visitaCarcelComparacionPlanilla(array $filas): string
{
    $planilla = new Spreadsheet;
    $planilla->getActiveSheet()->fromArray([
        ['RIT', 'AÑOCAUSA', 'RUC'],
        ...$filas,
    ]);
    $path = tempnam(sys_get_temp_dir(), 'visita-carcel-comparacion-');
    (new Xlsx($planilla))->save($path);

    try {
        return file_get_contents($path);
    } finally {
        @unlink($path);
    }
}

function visitaCarcelPlanillaMultiple(array $hojas): string
{
    $planilla = new Spreadsheet;
    $planilla->removeSheetByIndex(0);

    foreach ($hojas as [$titulo, $penal, $ruc]) {
        $hoja = $planilla->createSheet()->setTitle($titulo);
        $hoja->fromArray([
            ['Lugar de Reclusion', $penal],
            ['Tribunal', 'Rit', 'Año', 'Ruc', 'Imputado', 'RUT', 'Delito', 'Fec. Ingreso P.P.', 'Plazo Cierre', 'Ultima Rev. P.P.', 'Juez', 'Defensor', 'Fec. Ult. Res.', 'Ult. Resolución'],
            ['4 TOP Santiago', 200, 2026, $ruc, 'Imputado de prueba', '12.345.678-9', 'Delito de prueba', 46200, 46250, 46251, 'Juez', 'Defensor', 46252, 'Resolucion'],
        ]);
    }

    $path = tempnam(sys_get_temp_dir(), 'visita-carcel-multiple-');
    (new Xls($planilla))->save($path);

    try {
        return file_get_contents($path);
    } finally {
        @unlink($path);
    }
}
