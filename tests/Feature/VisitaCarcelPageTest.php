<?php

use App\Livewire\VisitaCarcelPage;
use App\Models\Penal;
use App\Models\User;
use App\Models\VisitaCarcelCausa;
use App\Models\VisitaCarcelImputado;
use App\Models\VisitaCarcelImputadoHistorial;
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
        ->set('ruc', '2301234567-8')
        ->set('rit', '142-2026')
        ->call('guardarCausa')
        ->assertHasNoErrors();

    $causa = VisitaCarcelCausa::query()->where('ruc', '2301234567-8')->firstOrFail();

    expect($causa->rit)->toBe('142-2026')
        ->and($causa->created_by)->toBe($user->id);

    expect($causa->imputados()->count())->toBe(0);
});

test('causes are paginated and searching returns to the first page', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('visita_carcel.ver');

    foreach (range(1, 21) as $number) {
        VisitaCarcelCausa::query()->create([
            'ruc' => sprintf('230123%04d-8', $number),
            'rit' => $number.'-2026',
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

function visitaCarcelManager(): User
{
    $user = User::factory()->create();
    $user->givePermissionTo(['visita_carcel.ver', 'visita_carcel.gestionar']);

    return $user;
}

function visitaCarcelPlanilla(array $filas, string $extension, string $penal = 'Penal', bool $columnasResiduales = false): string
{
    $planilla = new Spreadsheet;
    $formatoValido = count($filas[0] ?? []) === 10;
    $planilla->getActiveSheet()->fromArray([
        ['Lugar de Reclusion', $penal],
        $formatoValido ? ['Tribunal', 'Rit', 'Año', 'Ruc', 'Imputado', 'RUT', 'Delito', 'Fec. Ingreso P.P.', 'Plazo Cierre', 'Ultima Rev. P.P.', 'Juez', 'Defensor', 'Fec. Ult. Res.', 'Ult. Resolución'] : $filas[0],
        ...($formatoValido ? array_map(fn (array $fila) => ['4 TOP Santiago', $fila[0], $fila[1], $fila[2], 'Imputado de prueba', $fila[3], 'Delito de prueba', 46200, $fila[4], $fila[5], $fila[6], $fila[7], $fila[8], $fila[9]], $filas) : []),
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
