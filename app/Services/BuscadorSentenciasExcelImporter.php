<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class BuscadorSentenciasExcelImporter
{
    /** @var array<string, array<int, string>> */
    protected array $headerAliases = [
        'ruc' => ['RUC'],
        'rit' => ['RIT'],
        'ano' => ['AÑO', 'ANO'],
        'nombre_partes' => ['NOMBRE DE LAS PARTES'],
        'materia' => ['MATERIA'],
        'fecha_decision' => ['FECHA DECISION'],
        'glosa_decision' => ['GLOSA DECISION'],
        'juez' => ['JUEZ'],
        'instancia' => ['INSTANCIA'],
    ];

    public function import(string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new RuntimeException("No se encontro el archivo a importar: {$filePath}");
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $headerRow = $this->detectHeaderRow($sheet, $highestRow);

        if (! $headerRow) {
            throw new RuntimeException("No se encontro la fila de encabezados (columna A = 'RUC').");
        }

        $headerMap = $this->buildHeaderMap($sheet, $headerRow);

        foreach (array_keys($this->headerAliases) as $requiredField) {
            if (! isset($headerMap[$requiredField])) {
                throw new RuntimeException("No se encontro la columna requerida en el Excel: {$requiredField}");
            }
        }

        $startRow = $headerRow + 1;
        $batch = [];
        $batchSize = 500;
        $totalRows = 0;
        $insertedRows = 0;
        $omittedRows = 0;
        $duplicateRows = 0;
        $now = now();

        $existingKeys = $this->loadExistingKeys();

        for ($row = $startRow; $row <= $highestRow; $row++) {
            $ruc = trim((string) $this->valueFor($sheet, $row, $headerMap, 'ruc'));
            $rit = $this->valueFor($sheet, $row, $headerMap, 'rit');
            $ano = $this->valueFor($sheet, $row, $headerMap, 'ano');

            if ($ruc === '' && (string) $rit === '' && (string) $ano === '') {
                continue;
            }

            if ($this->shouldSkipRow($ruc)) {
                $omittedRows++;

                continue;
            }

            $totalRows++;

            $record = [
                'ruc' => $ruc !== '' ? $ruc : null,
                'rit' => is_numeric($rit) ? (int) $rit : null,
                'ano' => is_numeric($ano) ? (int) $ano : null,
                'nombre_partes' => $this->nullableString($this->valueFor($sheet, $row, $headerMap, 'nombre_partes')),
                'materia' => $this->nullableString($this->valueFor($sheet, $row, $headerMap, 'materia')),
                'fecha_decision' => $this->parseDate($this->valueFor($sheet, $row, $headerMap, 'fecha_decision')),
                'glosa_decision' => $this->nullableString($this->valueFor($sheet, $row, $headerMap, 'glosa_decision')),
                'juez' => $this->nullableString($this->valueFor($sheet, $row, $headerMap, 'juez')),
                'instancia' => $this->nullableString($this->valueFor($sheet, $row, $headerMap, 'instancia')),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $dedupeKey = $this->buildDedupeKey($record);
            if (isset($existingKeys[$dedupeKey])) {
                $duplicateRows++;

                continue;
            }

            $existingKeys[$dedupeKey] = true;
            $batch[] = $record;

            if (count($batch) >= $batchSize) {
                DB::table('buscadorsentencias')->insert($batch);
                $insertedRows += count($batch);
                $batch = [];
            }
        }

        if (! empty($batch)) {
            DB::table('buscadorsentencias')->insert($batch);
            $insertedRows += count($batch);
        }

        return [
            'total_filas_validas' => $totalRows,
            'filas_insertadas' => $insertedRows,
            'filas_omitidas' => $omittedRows,
            'filas_duplicadas' => $duplicateRows,
        ];
    }

    protected function loadExistingKeys(): array
    {
        $keys = [];

        DB::table('buscadorsentencias')
            ->select(['id', 'ruc', 'rit', 'ano', 'fecha_decision', 'glosa_decision'])
            ->orderBy('id')
            ->chunkById(1000, function ($rows) use (&$keys): void {
                foreach ($rows as $row) {
                    $record = [
                        'ruc' => $row->ruc,
                        'rit' => $row->rit,
                        'ano' => $row->ano,
                        'fecha_decision' => $row->fecha_decision,
                        'glosa_decision' => $row->glosa_decision,
                    ];

                    $keys[$this->buildDedupeKey($record)] = true;
                }
            });

        return $keys;
    }

    protected function buildDedupeKey(array $record): string
    {
        $ruc = mb_strtoupper(trim((string) ($record['ruc'] ?? '')));
        $rit = (string) ((int) ($record['rit'] ?? 0));
        $ano = (string) ((int) ($record['ano'] ?? 0));
        $fecha = trim((string) ($record['fecha_decision'] ?? ''));
        $glosa = mb_strtoupper(trim((string) ($record['glosa_decision'] ?? '')));

        return implode('|', [$ruc, $rit, $ano, $fecha, $glosa]);
    }

    protected function detectHeaderRow($sheet, int $highestRow): ?int
    {
        for ($row = 1; $row <= min($highestRow, 25); $row++) {
            $firstCell = $this->normalizeHeader((string) $sheet->getCell("A{$row}")->getValue());
            if ($firstCell === 'RUC') {
                return $row;
            }

            $secondCell = $this->normalizeHeader((string) $sheet->getCell("B{$row}")->getValue());
            $thirdCell = $this->normalizeHeader((string) $sheet->getCell("C{$row}")->getValue());

            if ($firstCell === 'RUC' || ($secondCell === 'RIT' && in_array($thirdCell, ['ANO', 'AÑO'], true))) {
                return $row;
            }
        }

        return null;
    }

    protected function buildHeaderMap($sheet, int $headerRow): array
    {
        $highestColumnIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());
        $normalizedHeaderToColumn = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $rawHeader = (string) $sheet->getCellByColumnAndRow($col, $headerRow)->getValue();
            $normalized = $this->normalizeHeader($rawHeader);

            if ($normalized !== '') {
                $normalizedHeaderToColumn[$normalized] = $col;
            }
        }

        $fieldToColumn = [];

        foreach ($this->headerAliases as $field => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizeHeader($alias);
                if (isset($normalizedHeaderToColumn[$normalizedAlias])) {
                    $fieldToColumn[$field] = $normalizedHeaderToColumn[$normalizedAlias];
                    break;
                }
            }
        }

        return $fieldToColumn;
    }

    protected function valueFor($sheet, int $row, array $headerMap, string $field): mixed
    {
        $column = $headerMap[$field] ?? null;

        if (! is_int($column)) {
            return null;
        }

        return $sheet->getCellByColumnAndRow($column, $row)->getValue();
    }

    protected function nullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    protected function parseDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_numeric($value)) {
            return ExcelDate::excelToDateTimeObject($value)->format('Y-m-d');
        }

        $raw = trim((string) $value);
        $raw = str_replace('*', '', $raw);
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('d/m/Y', $raw)->format('Y-m-d');
        } catch (\Throwable) {
            // noop
        }

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normalizeHeader(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        $withoutAccents = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if (is_string($withoutAccents) && $withoutAccents !== '') {
            $value = $withoutAccents;
        }

        $value = mb_strtoupper($value);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    protected function shouldSkipRow(string $ruc): bool
    {
        $check = mb_strtoupper(trim($ruc));

        if ($check === '') {
            return false;
        }

        return str_starts_with($check, 'TOTAL')
            || str_starts_with($check, 'JUEZ PRESIDENTE')
            || str_starts_with($check, 'FIRMA')
            || str_starts_with($check, 'NOMBRE')
            || str_starts_with($check, 'CORRESPONDIENTES AL MES')
            || str_starts_with($check, 'REGION:')
            || str_starts_with($check, 'CORTE DE APELACIONES:')
            || str_starts_with($check, 'SISTEMA DE APOYO')
            || str_starts_with($check, '4O TRIBUNAL')
            || str_starts_with($check, 'BOLETIN MENSUAL');
    }
}
