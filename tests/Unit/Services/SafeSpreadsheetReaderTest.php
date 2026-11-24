<?php

use App\Services\SafeSpreadsheetReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls as XlsWriter;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;

function spreadsheetFixture(string $writer, string $extension): string
{
    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray([['RIT', 'Nombre'], ['123-2026', 'Prueba']]);
    $path = tempnam(sys_get_temp_dir(), 'sheet').'.'.$extension;
    (new $writer($spreadsheet))->save($path);

    return $path;
}

test('it reads an xlsx stored without its extension, as Livewire temporary uploads are', function () {
    $path = spreadsheetFixture(XlsxWriter::class, 'bin');

    $sheet = (new SafeSpreadsheetReader)->load($path, allowXls: false, maxRows: 10, maxColumns: 5);

    expect($sheet->getActiveSheet()->getCell('B2')->getValue())->toBe('Prueba');
    unlink($path);
});

test('it reads an xls without extension only when xls is allowed', function () {
    $path = spreadsheetFixture(XlsWriter::class, 'bin');
    $reader = new SafeSpreadsheetReader;

    expect($reader->loadAll($path, allowXls: true, maxRows: 10, maxColumns: 5)->getSheetCount())->toBe(1)
        ->and(fn () => $reader->load($path, allowXls: false, maxRows: 10, maxColumns: 5))
        ->toThrow(RuntimeException::class, 'Solo se permiten archivos XLSX.');
    unlink($path);
});

test('it rejects contents that are not a spreadsheet', function () {
    $path = tempnam(sys_get_temp_dir(), 'sheet').'.bin';
    file_put_contents($path, "RIT,Nombre\n123-2026,Prueba\n");

    expect(fn () => (new SafeSpreadsheetReader)->load($path, allowXls: true, maxRows: 10, maxColumns: 5))
        ->toThrow(RuntimeException::class, 'El archivo debe ser XLSX o XLS.');
    unlink($path);
});

test('it still rejects a spreadsheet whose extension does not match its contents', function () {
    $path = spreadsheetFixture(XlsWriter::class, 'xlsx');

    expect(fn () => (new SafeSpreadsheetReader)->load($path, allowXls: true, maxRows: 10, maxColumns: 5))
        ->toThrow(RuntimeException::class, 'El contenido del archivo no coincide con el formato permitido.');
    unlink($path);
});
