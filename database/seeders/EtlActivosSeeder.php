<?php

namespace Database\Seeders;

use App\Models\Activo;
use App\Models\Sector;
use App\Services\SafeSpreadsheetReader;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EtlActivosSeeder extends Seeder
{
    private const SOURCE_FILE = 'ETL AF modificado.xlsx';

    /** @var array<string, array<int, array<string, string>>> */
    private const CORRECTIONS = [
        'Hoja1' => [
            445 => ['sector' => 'ADM'],
            446 => ['sector' => 'ADM'],
            447 => ['sector' => 'ADM'],
            448 => ['sector' => 'ADM'],
            449 => ['sector' => 'ADM'],
            450 => ['sector' => 'ADM'],
        ],
        'PC' => [
            25 => ['sector' => '9_HALL_CENTRAL'],
            58 => ['nombre' => 'Pantalla HP P22'],
            60 => ['nombre' => 'HP LaserJet Managed E40040dn'],
            95 => ['sector' => '9_906'],
        ],
    ];

    public function run(SafeSpreadsheetReader $reader): void
    {
        $path = base_path('docs/'.self::SOURCE_FILE);
        $spreadsheet = $reader->loadAll($path, allowXls: false, maxRows: 25000, maxColumns: 6);

        try {
            $this->call(SectorSeeder::class);

            DB::transaction(function () use ($spreadsheet): void {
                foreach (['Hoja1', 'PC'] as $sheetName) {
                    $sheet = $spreadsheet->getSheetByName($sheetName);

                    if ($sheet === null) {
                        throw new RuntimeException("No existe la hoja {$sheetName}.");
                    }

                    $this->seedSheet($sheetName, $sheet->toArray(null, true, true, true));
                }
            });
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /** @param array<int, array<string, mixed>> $rows */
    private function seedSheet(string $sheetName, array $rows): void
    {
        if (($rows[1] ?? []) !== ['A' => 'Nombre', 'B' => 'sector_id', 'C' => 'codigo_inventario', 'D' => 'numero_serie', 'E' => 'estado', 'F' => 'Observaciones']) {
            throw new RuntimeException("Los encabezados de {$sheetName} no son válidos.");
        }

        foreach ($rows as $rowNumber => $row) {
            if ($rowNumber === 1 || $this->blank($row)) {
                continue;
            }

            $data = [
                'nombre' => $this->clean($row['A'] ?? null),
                'sector' => $this->clean($row['B'] ?? null),
                'codigo_inventario' => $this->nullable($row['C'] ?? null),
                'numero_serie' => $this->nullable($row['D'] ?? null),
                'observaciones' => $this->nullable($row['F'] ?? null),
            ];

            foreach (self::CORRECTIONS[$sheetName][$rowNumber] ?? [] as $field => $value) {
                $data[$field] = $value;
            }

            if ($data['nombre'] === '' || $data['sector'] === '') {
                throw new RuntimeException("Falta nombre o sector en {$sheetName}, fila {$rowNumber}.");
            }

            $sector = Sector::query()->where('identificador_mapa', $data['sector'])->first();

            if ($sector === null) {
                throw new RuntimeException("No existe el sector {$data['sector']} en {$sheetName}, fila {$rowNumber}.");
            }

            $source = sprintf('[%s:%s:%d]', self::SOURCE_FILE, $sheetName, $rowNumber);
            $attributes = [
                'nombre' => $data['nombre'],
                'sector_id' => $sector->id,
                'codigo_inventario' => $data['codigo_inventario'],
                'numero_serie' => $data['numero_serie'],
                'estado' => $data['sector'] === 's03' ? 'Baja' : 'Operativo',
                'observaciones' => $data['observaciones'],
                'origen_importacion' => $source,
            ];

            $activo = Activo::query()
                ->where('origen_importacion', $source)
                ->orWhere(function ($query) use ($source) {
                    $query->whereNull('origen_importacion')->where('observaciones', 'like', "%{$source}%");
                })
                ->first();

            if ($activo) {
                $activo->update($attributes);
            } else {
                Activo::query()->create($attributes);
            }
        }
    }

    /** @param array<string, mixed> $row */
    private function blank(array $row): bool
    {
        return collect($row)->every(fn (mixed $value): bool => $this->clean($value) === '');
    }

    private function clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value) ?? '');
    }

    private function nullable(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value === '' ? null : $value;
    }
}
