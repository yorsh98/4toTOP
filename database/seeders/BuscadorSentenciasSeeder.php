<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class BuscadorSentenciasSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/sentencias_para_el_buscador.xlsx');

        if (!file_exists($path)) {
            throw new \RuntimeException("No se encontró el archivo XLSX en: {$path}");
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();

        // Buscamos la fila de encabezados (en tu archivo está en la fila 4)
        $highestRow = $sheet->getHighestRow();

        $headerRow = null;
        for ($r = 1; $r <= min($highestRow, 20); $r++) {
            $v = trim((string) $sheet->getCell("A{$r}")->getValue());
            if (mb_strtoupper($v) === 'RUC') {
                $headerRow = $r;
                break;
            }
        }

        if (!$headerRow) {
            throw new \RuntimeException("No se encontró la fila de encabezados (celda A='RUC').");
        }

        $startRow = $headerRow + 1;

        $batch = [];
        $batchSize = 500;
        $now = now();

        for ($r = $startRow; $r <= $highestRow; $r++) {
            $ruc = trim((string) $sheet->getCell("A{$r}")->getValue());
            $rit = $sheet->getCell("B{$r}")->getValue();
            $ano = $sheet->getCell("C{$r}")->getValue();

            // Si la fila está vacía, la saltamos
            if ($ruc === '' && (string)$rit === '' && (string)$ano === '') {
                continue;
            }

            $nombrePartes = trim((string) $sheet->getCell("D{$r}")->getValue());
            $materia      = trim((string) $sheet->getCell("E{$r}")->getValue());

            // FECHA DECISION (columna F): puede venir como DateTime, número Excel o string
            $fechaCell = $sheet->getCell("F{$r}")->getValue();
            $fechaDecision = null;

            if ($fechaCell instanceof \DateTimeInterface) {
                $fechaDecision = $fechaCell->format('Y-m-d');
            } elseif (is_numeric($fechaCell)) {
                $fechaDecision = ExcelDate::excelToDateTimeObject($fechaCell)->format('Y-m-d');
            } else {
                $tmp = trim((string) $fechaCell);
                if ($tmp !== '') {
                    // por si viene "2024-02-05 00:00:00" o "2024-02-05"
                    try {
                        $fechaDecision = \Carbon\Carbon::parse($tmp)->format('Y-m-d');
                    } catch (\Throwable $e) {
                        $fechaDecision = null;
                    }
                }
            }

            $glosa = trim((string) $sheet->getCell("G{$r}")->getValue());
            $juez  = trim((string) $sheet->getCell("H{$r}")->getValue());
            $inst  = trim((string) $sheet->getCell("I{$r}")->getValue());

            // Normalizaciones leves
            $rit = is_numeric($rit) ? (int) $rit : null;
            $ano = is_numeric($ano) ? (int) $ano : null;
            $ruc = $ruc !== '' ? $ruc : null;

            $batch[] = [
                'ruc'            => $ruc,
                'rit'            => $rit,
                'ano'            => $ano,
                'nombre_partes'  => $nombrePartes !== '' ? $nombrePartes : null,
                'materia'        => $materia !== '' ? $materia : null,
                'fecha_decision' => $fechaDecision,
                'glosa_decision' => $glosa !== '' ? $glosa : null,
                'juez'           => $juez !== '' ? $juez : null,
                'instancia'      => $inst !== '' ? $inst : null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];

            if (count($batch) >= $batchSize) {
                DB::table('buscadorsentencias')->insert($batch);
                $batch = [];
            }
        }

        if (!empty($batch)) {
            DB::table('buscadorsentencias')->insert($batch);
        }
    }
}