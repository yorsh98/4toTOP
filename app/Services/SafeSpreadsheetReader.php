<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use RuntimeException;

class SafeSpreadsheetReader
{
    public function load(string $path, bool $allowXls, int $maxRows, int $maxColumns): Spreadsheet
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('El archivo no está disponible para su lectura.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $reader = match ($extension) {
            'xlsx' => new Xlsx,
            'xls' => $allowXls ? new Xls : throw new RuntimeException('Solo se permiten archivos XLSX.'),
            default => throw new RuntimeException('El archivo debe ser XLSX'.($allowXls ? ' o XLS.' : '.')),
        };

        if (! $reader->canRead($path)) {
            throw new RuntimeException('El contenido del archivo no coincide con el formato permitido.');
        }

        $worksheets = $reader->listWorksheetInfo($path);
        $firstWorksheet = $worksheets[0] ?? null;

        if ($firstWorksheet === null
            || $firstWorksheet['totalRows'] > $maxRows
            || $firstWorksheet['totalColumns'] > $maxColumns) {
            throw new RuntimeException('La planilla excede los límites permitidos.');
        }

        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly([$firstWorksheet['worksheetName']]);

        return $reader->load($path);
    }
}
