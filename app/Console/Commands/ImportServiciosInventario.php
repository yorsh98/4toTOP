<?php

namespace App\Console\Commands;

use App\Models\Activo;
use App\Models\Sector;
use App\Services\SafeSpreadsheetReader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportServiciosInventario extends Command
{
    protected $signature = 'servicios:importar-inventario {file : Ruta del archivo XLSX} {--replace : Elimina los activos actuales antes de importar}';

    protected $description = 'Importa activos fijos de Servicios desde un archivo Excel';

    public function handle(): int
    {
        $file = (string) $this->argument('file');

        if (! is_file($file)) {
            $this->error("No existe el archivo: {$file}");

            return self::FAILURE;
        }

        $spreadsheet = null;

        try {
            $spreadsheet = app(SafeSpreadsheetReader::class)->load(
                $file,
                allowXls: false,
                maxRows: 25000,
                maxColumns: 6,
            );
            $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        } finally {
            $spreadsheet?->disconnectWorksheets();
        }

        $imported = 0;
        $createdSectors = 0;
        $currentPiso = null;
        $currentSector = null;

        DB::transaction(function () use ($rows, &$imported, &$createdSectors, &$currentPiso, &$currentSector) {
            if ($this->option('replace')) {
                Activo::query()->delete();
            }

            foreach ($rows as $rowNumber => $row) {
                if ($rowNumber === 1 || $this->isBlankRow($row)) {
                    if ($rowNumber !== 1) {
                        $currentSector = null;
                    }

                    continue;
                }

                $codigo = $this->clean($row['A'] ?? null);
                $nombre = $this->clean($row['B'] ?? null);
                $piso = $this->clean($row['C'] ?? null);
                $sector = $this->clean($row['D'] ?? null);
                $observacion = $this->clean($row['E'] ?? null);
                $referencia = $this->clean($row['F'] ?? null);

                if ($nombre === '') {
                    continue;
                }

                if ($piso !== '') {
                    $currentPiso = $this->normalizePiso($piso);
                    $currentSector = $sector !== '' ? $sector : null;
                } elseif ($sector !== '') {
                    $currentSector = $sector;
                }

                $resolvedPiso = $currentPiso ?: '8VO';
                $resolvedSector = $currentSector ?: "{$resolvedPiso} SIN SECTOR";
                $identifier = $this->sectorIdentifier($resolvedPiso, $resolvedSector);

                $sectorModel = Sector::query()->firstOrCreate(
                    ['identificador_mapa' => $identifier],
                    [
                        'nombre' => $this->sectorName($resolvedPiso, $resolvedSector),
                        'piso' => $resolvedPiso,
                    ]
                );

                if ($sectorModel->wasRecentlyCreated) {
                    $createdSectors++;
                }

                $codigoInventario = $codigo !== '' ? $codigo : "SIN AF FILA {$rowNumber}";
                $observaciones = collect([
                    $observacion !== '' ? "Obs: {$observacion}" : null,
                    $referencia !== '' ? "Referencia: {$referencia}" : null,
                    "Fila Excel: {$rowNumber}",
                ])->filter()->implode(' | ');

                Activo::query()->updateOrCreate(
                    [
                        'codigo_inventario' => $codigoInventario,
                        'sector_id' => $sectorModel->id,
                        'nombre' => $nombre,
                        'observaciones' => $observaciones,
                    ],
                    [
                        'numero_serie' => null,
                        'estado' => 'Operativo',
                    ]
                );

                $imported++;
            }
        });

        $this->info("Importados {$imported} activos. Sectores nuevos: {$createdSectors}.");

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach (['A', 'B', 'C', 'D', 'E', 'F'] as $column) {
            if ($this->clean($row[$column] ?? null) !== '') {
                return false;
            }
        }

        return true;
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    }

    private function normalizePiso(string $piso): string
    {
        return mb_strtoupper($piso);
    }

    private function sectorIdentifier(string $piso, string $sector): string
    {
        $sector = mb_strtoupper($sector);

        if (str_contains($sector, 'SIN SECTOR')) {
            return $sector;
        }

        if ($piso === '8VO') {
            return $sector;
        }

        return "{$piso} {$sector}";
    }

    private function sectorName(string $piso, string $sector): string
    {
        if (str_contains($sector, 'SIN SECTOR')) {
            return "{$piso} sin sector definido";
        }

        return "{$piso} {$sector}";
    }
}
