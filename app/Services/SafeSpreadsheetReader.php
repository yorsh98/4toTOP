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

        $reader = $this->readerFor($path, $allowXls);

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

    public function loadAll(string $path, bool $allowXls, int $maxRows, int $maxColumns): Spreadsheet
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('El archivo no está disponible para su lectura.');
        }

        $reader = $this->readerFor($path, $allowXls);

        $worksheets = $reader->listWorksheetInfo($path);

        if ($worksheets === [] || collect($worksheets)->contains(fn (array $worksheet) => $worksheet['totalRows'] > $maxRows || $worksheet['totalColumns'] > $maxColumns)) {
            throw new RuntimeException('La planilla excede los límites permitidos.');
        }

        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);
        $reader->setLoadSheetsOnly(array_column($worksheets, 'worksheetName'));

        return $reader->load($path);
    }

    /**
     * Picks the reader from the file extension and, when the path carries none we trust (Livewire stores
     * temporary uploads as `.bin` since 3.8.12), from the file contents. Either way the contents must match.
     */
    private function readerFor(string $path, bool $allowXls): Xlsx|Xls
    {
        $reader = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'xlsx' => new Xlsx,
            'xls' => new Xls,
            default => $this->detectReader($path, $allowXls),
        };

        if ($reader instanceof Xls && ! $allowXls) {
            throw new RuntimeException('Solo se permiten archivos XLSX.');
        }

        if (! $reader->canRead($path)) {
            throw new RuntimeException('El contenido del archivo no coincide con el formato permitido.');
        }

        return $reader;
    }

    private function detectReader(string $path, bool $allowXls): Xlsx|Xls
    {
        foreach ([new Xlsx, new Xls] as $reader) {
            if ($reader->canRead($path)) {
                return $reader;
            }
        }

        throw new RuntimeException('El archivo debe ser XLSX'.($allowXls ? ' o XLS.' : '.'));
    }
}
