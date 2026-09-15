<?php

use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoImportacion;
use App\Models\InsumoMovimiento;
use App\Models\Sector;
use App\Models\User;
use App\Services\InsumoStockExcelImporter;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

/** @param array<int, array{codigo: string, nombre: string, unidad: string, cantidad: int, bodega: string}> $items */
function createInsumoStockXls(array $items, bool $complete = true): string
{
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', $complete ? 'TODAS LAS BODEGAS' : 'BODEGA PARCIAL');
    $sheet->fromArray(['Bodega', 'Codigo', 'Nombre Articulo', 'UME', 'Stock Actual', '', '', '', '', '', '', '', '', 'UBICACIÓN'], null, 'A12');

    foreach ($items as $index => $item) {
        $row = 13 + $index;
        $sheet->setCellValue("A{$row}", 1);
        $sheet->setCellValueExplicit("B{$row}", $item['codigo'], DataType::TYPE_STRING);
        $sheet->setCellValue("C{$row}", $item['nombre']);
        $sheet->setCellValue("D{$row}", $item['unidad']);
        $sheet->setCellValue("E{$row}", $item['cantidad']);
        $sheet->setCellValue("N{$row}", "BODEGA {$item['bodega']}");
    }

    $sheet->setCellValue('A'.(14 + count($items)), 'TOTAL STOCK ACTUAL');

    $temporaryPath = tempnam(sys_get_temp_dir(), 'insumos-stock-');
    $path = "{$temporaryPath}.xls";
    unlink($temporaryPath);
    (new Xls($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    return $path;
}

afterEach(function () {
    foreach (glob(sys_get_temp_dir().'/insumos-stock-*.xls') ?: [] as $path) {
        unlink($path);
    }
});

test('preview reads stock rows and their mapped sectors', function () {
    $sector = Sector::factory()->create(['nombre' => 'Bodega resmas', 'identificador_mapa' => '818']);
    $path = createInsumoStockXls([
        ['codigo' => '141115070002', 'nombre' => 'Papel carta', 'unidad' => 'RE', 'cantidad' => 17, 'bodega' => '818'],
    ]);

    $preview = app(InsumoStockExcelImporter::class)->preview($path);

    expect($preview['completo'])->toBeTrue()
        ->and($preview['filas'])->toBe(1)
        ->and($preview['total_unidades'])->toBe(17)
        ->and($preview['muestras'][0]['codigo'])->toBe('141115070002')
        ->and($preview['muestras'][0]['sector_id'])->toBe($sector->id);
});

test('preview shows the stock difference that requires approval', function () {
    $sector = Sector::factory()->create(['identificador_mapa' => '818']);
    $insumo = Insumo::create(['codigo' => 'LAP-NEGRO', 'nombre' => 'Lapiz negro', 'unidad_medida' => 'UN']);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 10]);
    $path = createInsumoStockXls([
        ['codigo' => 'LAP-NEGRO', 'nombre' => 'Lapiz negro', 'unidad' => 'UN', 'cantidad' => 30, 'bodega' => '818'],
    ]);

    $cambio = app(InsumoStockExcelImporter::class)->preview($path)['cambios'][0];

    expect($cambio['actual'])->toBe(10)
        ->and($cambio['propuesto'])->toBe(30)
        ->and($cambio['diferencia'])->toBe(20);
});

test('complete preview lists existing balances that will be reset to zero', function () {
    $sector = Sector::factory()->create(['identificador_mapa' => '818']);
    $insumo = Insumo::create(['codigo' => 'AUSENTE-001', 'nombre' => 'Insumo ausente', 'unidad_medida' => 'UN']);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 10]);
    $path = createInsumoStockXls([
        ['codigo' => 'PRESENTE-001', 'nombre' => 'Insumo presente', 'unidad' => 'UN', 'cantidad' => 2, 'bodega' => '818'],
    ]);

    $cambio = collect(app(InsumoStockExcelImporter::class)->preview($path)['cambios'])->firstWhere('codigo', 'AUSENTE-001');

    expect($cambio)->toMatchArray(['actual' => 10, 'propuesto' => 0, 'diferencia' => -10]);
});

test('complete stock import synchronizes balances and is idempotent by file hash', function () {
    $sector807 = Sector::factory()->create(['nombre' => 'Bodega oficina', 'identificador_mapa' => '807']);
    $sector811 = Sector::factory()->create(['nombre' => 'Bodega computacion', 'identificador_mapa' => '811']);
    $actor = User::factory()->create();
    $initialPath = createInsumoStockXls([
        ['codigo' => 'INS-001', 'nombre' => 'Papel carta', 'unidad' => 'RE', 'cantidad' => 10, 'bodega' => '807'],
        ['codigo' => 'INS-002', 'nombre' => 'Pilas', 'unidad' => 'UN', 'cantidad' => 3, 'bodega' => '811'],
    ]);

    $importer = app(InsumoStockExcelImporter::class);
    $firstImport = $importer->importar($initialPath, 'stock-inicial.xls', $actor->id);
    $duplicateImport = $importer->importar($initialPath, 'stock-inicial.xls', $actor->id);

    expect($firstImport['aplicada'])->toBeTrue()
        ->and($duplicateImport['aplicada'])->toBeFalse()
        ->and(InsumoImportacion::query()->count())->toBe(1)
        ->and(Insumo::query()->count())->toBe(2)
        ->and(InsumoMovimiento::query()->count())->toBe(2);

    $nextPath = createInsumoStockXls([
        ['codigo' => 'INS-001', 'nombre' => 'Papel carta', 'unidad' => 'RE', 'cantidad' => 4, 'bodega' => '807'],
    ]);
    $importer->importar($nextPath, 'stock-actualizado.xls', $actor->id);

    $firstInsumo = Insumo::query()->where('codigo', 'INS-001')->firstOrFail();
    $secondInsumo = Insumo::query()->where('codigo', 'INS-002')->firstOrFail();

    expect(InsumoExistencia::query()->where('insumo_id', $firstInsumo->id)->where('sector_id', $sector807->id)->value('cantidad'))->toBe(4)
        ->and(InsumoExistencia::query()->where('insumo_id', $secondInsumo->id)->where('sector_id', $sector811->id)->value('cantidad'))->toBe(0)
        ->and(InsumoMovimiento::query()->count())->toBe(4);
});
