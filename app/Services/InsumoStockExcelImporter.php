<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoImportacion;
use App\Models\Sector;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class InsumoStockExcelImporter
{
    public function __construct(
        private readonly SafeSpreadsheetReader $spreadsheetReader,
        private readonly InsumoStockService $stockService,
    ) {}

    /**
     * @return array{archivo_hash: string, completo: bool, filas: int, total_unidades: int, muestras: array<int, array{codigo: string, nombre: string, unidad_medida: string, cantidad: int, sector_id: int, sector: string}>, cambios: array<int, array{codigo: string, nombre: string, unidad_medida: string, sector: string, actual: int, propuesto: int, diferencia: int}>}
     */
    public function preview(string $path): array
    {
        $data = $this->read($path);

        $insumos = Insumo::query()->whereIn('codigo', array_column($data['items'], 'codigo'))->get()->keyBy('codigo');
        $existencias = InsumoExistencia::query()
            ->whereIn('insumo_id', $insumos->pluck('id'))
            ->get()
            ->keyBy(fn (InsumoExistencia $existencia): string => "{$existencia->insumo_id}:{$existencia->sector_id}");

        $cambios = array_map(function (array $item) use ($insumos, $existencias): array {
            $insumo = $insumos->get($item['codigo']);
            $actual = (int) ($insumo ? $existencias->get("{$insumo->id}:{$item['sector_id']}")?->cantidad : 0);

            return [
                'codigo' => $item['codigo'],
                'nombre' => $item['nombre'],
                'unidad_medida' => $item['unidad_medida'],
                'sector' => $item['sector'],
                'actual' => $actual,
                'propuesto' => $item['cantidad'],
                'diferencia' => $item['cantidad'] - $actual,
            ];
        }, $data['items']);

        if ($data['completo']) {
            $importados = collect($data['items'])
                ->map(fn (array $item): ?string => ($insumos->get($item['codigo'])?->id).":{$item['sector_id']}")
                ->filter()
                ->flip();

            InsumoExistencia::query()->with(['insumo', 'sector'])->where('cantidad', '>', 0)->get()
                ->reject(fn (InsumoExistencia $existencia): bool => $importados->has("{$existencia->insumo_id}:{$existencia->sector_id}"))
                ->each(function (InsumoExistencia $existencia) use (&$cambios): void {
                    $cambios[] = [
                        'codigo' => $existencia->insumo->codigo,
                        'nombre' => $existencia->insumo->nombre,
                        'unidad_medida' => $existencia->insumo->unidad_medida,
                        'sector' => $existencia->sector->nombre,
                        'actual' => $existencia->cantidad,
                        'propuesto' => 0,
                        'diferencia' => -$existencia->cantidad,
                    ];
                });
        }

        return [
            'archivo_hash' => hash_file('sha256', $path),
            'completo' => $data['completo'],
            'filas' => count($data['items']),
            'total_unidades' => array_sum(array_column($data['items'], 'cantidad')),
            'muestras' => array_slice($data['items'], 0, 20),
            'cambios' => $cambios,
        ];
    }

    /**
     * @return array{importacion: InsumoImportacion, aplicada: bool}
     */
    public function importar(string $path, string $archivoNombre, int $actorUserId): array
    {
        $data = $this->read($path);
        $hash = hash_file('sha256', $path);

        return DB::transaction(function () use ($data, $hash, $archivoNombre, $actorUserId): array {
            $now = now();
            $created = DB::table('insumo_importaciones')->insertOrIgnore([
                'archivo_nombre' => $archivoNombre,
                'archivo_hash' => $hash,
                'imported_by_user_id' => $actorUserId,
                'estado' => 'procesando',
                'filas_leidas' => 0,
                'insumos_creados' => 0,
                'saldos_actualizados' => 0,
                'sin_cambios' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $importacion = InsumoImportacion::query()
                ->where('archivo_hash', $hash)
                ->lockForUpdate()
                ->firstOrFail();

            if ($created === 0) {
                return ['importacion' => $importacion, 'aplicada' => false];
            }

            $operacionUuid = (string) Str::uuid();
            $insumosCreados = 0;
            $saldosActualizados = 0;
            $sinCambios = 0;
            $importedKeys = [];

            foreach ($data['items'] as $item) {
                $insumo = Insumo::query()->firstOrCreate(
                    ['codigo' => $item['codigo']],
                    [
                        'nombre' => $item['nombre'],
                        'unidad_medida' => $item['unidad_medida'],
                    ],
                );

                if ($insumo->wasRecentlyCreated) {
                    $insumosCreados++;
                } elseif ($insumo->nombre !== $item['nombre'] || $insumo->unidad_medida !== $item['unidad_medida']) {
                    $insumo->update([
                        'nombre' => $item['nombre'],
                        'unidad_medida' => $item['unidad_medida'],
                    ]);
                }

                $movimiento = $this->stockService->ajustarImportacion(
                    $insumo->id,
                    $item['sector_id'],
                    $item['cantidad'],
                    $actorUserId,
                    $importacion->id,
                    $operacionUuid,
                );

                $movimiento === null ? $sinCambios++ : $saldosActualizados++;
                $importedKeys["{$insumo->id}:{$item['sector_id']}"] = true;
            }

            if ($data['completo']) {
                InsumoExistencia::query()
                    ->orderBy('sector_id')
                    ->orderBy('insumo_id')
                    ->lockForUpdate()
                    ->get()
                    ->each(function (InsumoExistencia $existencia) use ($importedKeys, $actorUserId, $importacion, $operacionUuid, &$saldosActualizados): void {
                        if (isset($importedKeys["{$existencia->insumo_id}:{$existencia->sector_id}"]) || $existencia->cantidad === 0) {
                            return;
                        }

                        $this->stockService->ajustarImportacion(
                            $existencia->insumo_id,
                            $existencia->sector_id,
                            0,
                            $actorUserId,
                            $importacion->id,
                            $operacionUuid,
                        );
                        $saldosActualizados++;
                    });
            }

            $importacion->update([
                'estado' => 'completada',
                'filas_leidas' => count($data['items']),
                'insumos_creados' => $insumosCreados,
                'saldos_actualizados' => $saldosActualizados,
                'sin_cambios' => $sinCambios,
            ]);

            return ['importacion' => $importacion->fresh(), 'aplicada' => true];
        }, attempts: 3);
    }

    /**
     * @return array{completo: bool, items: array<int, array{codigo: string, nombre: string, unidad_medida: string, cantidad: int, sector_id: int, sector: string}>}
     */
    private function read(string $path): array
    {
        $spreadsheet = $this->spreadsheetReader->load($path, true, 1000, 20);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $headerRow = $this->headerRow($sheet);
            $headers = $this->headers($sheet, $headerRow);
            $items = [];

            for ($row = $headerRow + 1; $row <= $sheet->getHighestRow(); $row++) {
                $bodega = (string) $this->cellValue($sheet, $headers['bodega'], $row);
                $codigo = $this->code($this->cellValue($sheet, $headers['codigo'], $row));
                $nombre = trim((string) $this->cellValue($sheet, $headers['nombre'], $row));

                if (str_contains($this->normalize($bodega), 'TOTAL')) {
                    continue;
                }

                if ($codigo === '' && $nombre === '') {
                    continue;
                }

                if ($codigo === '' && str_contains($this->normalize($nombre), 'TOTAL')) {
                    continue;
                }

                if ($codigo === '' || $nombre === '') {
                    throw new DomainException("La fila {$row} no tiene codigo o nombre de articulo.");
                }

                $unidadMedida = trim((string) $this->cellValue($sheet, $headers['unidad_medida'], $row));
                $cantidad = $this->quantity($this->cellValue($sheet, $headers['cantidad'], $row), $row);
                $ubicacion = trim((string) $this->cellValue($sheet, $headers['ubicacion'], $row));
                $sector = Sector::query()->where('identificador_mapa', $this->sectorIdentifier($ubicacion, $row))->first();

                if ($unidadMedida === '') {
                    throw new DomainException("La fila {$row} no tiene unidad de medida.");
                }

                if ($sector === null) {
                    throw new DomainException("La bodega de la fila {$row} no existe como sector del sistema.");
                }

                $key = "{$codigo}:{$sector->id}";
                if (isset($items[$key])) {
                    throw new DomainException("El articulo {$codigo} se repite en la bodega {$sector->identificador_mapa}.");
                }

                $items[$key] = [
                    'codigo' => $codigo,
                    'nombre' => $nombre,
                    'unidad_medida' => $unidadMedida,
                    'cantidad' => $cantidad,
                    'sector_id' => $sector->id,
                    'sector' => $sector->nombre,
                ];
            }

            if ($items === []) {
                throw new DomainException('La planilla no contiene articulos para importar.');
            }

            return [
                'completo' => collect($sheet->toArray())->flatten()->contains(fn ($value): bool => str_contains($this->normalize((string) $value), 'TODAS LAS BODEGAS')),
                'items' => array_values($items),
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    private function headerRow($sheet): int
    {
        for ($row = 1; $row <= $sheet->getHighestRow(); $row++) {
            $headers = $this->headers($sheet, $row, false);

            if (isset($headers['bodega'], $headers['codigo'], $headers['nombre'], $headers['unidad_medida'], $headers['cantidad'], $headers['ubicacion'])) {
                return $row;
            }
        }

        throw new DomainException('No se encontro el encabezado de stock esperado.');
    }

    /** @return array<string, int> */
    private function headers($sheet, int $row, bool $required = true): array
    {
        $aliases = [
            'BODEGA' => 'bodega',
            'CODIGO' => 'codigo',
            'NOMBRE ARTICULO' => 'nombre',
            'UME' => 'unidad_medida',
            'STOCK ACTUAL' => 'cantidad',
            'UBICACION' => 'ubicacion',
        ];
        $headers = [];

        for ($column = 1; $column <= Coordinate::columnIndexFromString($sheet->getHighestColumn()); $column++) {
            $header = $aliases[$this->normalize((string) $this->cellValue($sheet, $column, $row))] ?? null;

            if ($header !== null) {
                $headers[$header] = $column;
            }
        }

        if ($required && count($headers) !== count($aliases)) {
            throw new DomainException('El encabezado de stock no contiene todas las columnas requeridas.');
        }

        return $headers;
    }

    private function cellValue($sheet, int $column, int $row): mixed
    {
        return $sheet->getCell(Coordinate::stringFromColumnIndex($column).$row)->getValue();
    }

    private function code(mixed $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return number_format($value, 0, '', '');
        }

        return trim((string) $value);
    }

    private function quantity(mixed $value, int $row): int
    {
        if (is_string($value) && ctype_digit($value)) {
            $value = (int) $value;
        }

        if ((! is_int($value) && ! is_float($value)) || $value < 0 || floor((float) $value) !== (float) $value) {
            throw new DomainException("La cantidad de la fila {$row} es invalida.");
        }

        return (int) $value;
    }

    private function sectorIdentifier(string $ubicacion, int $row): string
    {
        if (! preg_match('/^BODEGA\s+(.+)$/i', $ubicacion, $matches)) {
            throw new DomainException("La ubicacion de la fila {$row} es invalida.");
        }

        return trim($matches[1]);
    }

    private function normalize(string $value): string
    {
        return Str::upper(trim(Str::ascii($value)));
    }
}
