<?php

namespace App\Services;

use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class InsumoStockService
{
    public function ingresar(
        int $insumoId,
        int $sectorId,
        int $cantidad,
        int $actorUserId,
        ?string $observacion = null,
    ): InsumoMovimiento {
        $this->assertPositiveQuantity($cantidad);

        return DB::transaction(function () use ($insumoId, $sectorId, $cantidad, $actorUserId, $observacion): InsumoMovimiento {
            $existencia = $this->lockExistencia($insumoId, $sectorId, create: true);
            $stockAnterior = $existencia->cantidad;
            $stockPosterior = $stockAnterior + $cantidad;

            $existencia->update(['cantidad' => $stockPosterior]);

            return InsumoMovimiento::create([
                'operacion_uuid' => (string) Str::uuid(),
                'insumo_id' => $insumoId,
                'sector_id' => $sectorId,
                'tipo' => 'entrada',
                'cantidad' => $cantidad,
                'stock_anterior' => $stockAnterior,
                'stock_posterior' => $stockPosterior,
                'created_by_user_id' => $actorUserId,
                'observacion' => $observacion,
            ]);
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{insumo_id: int, sector_id: int, cantidad: int, solicitud_detalle_id?: int|null}>  $items
     * @return Collection<int, InsumoMovimiento>
     */
    public function asignar(array $items, int $actorUserId, ?int $asignadoAUserId, ?string $observacion = null, ?int $asignadoAFuncionarioId = null): Collection
    {
        $items = $this->normalizeItems($items);
        $operacionUuid = (string) Str::uuid();

        return DB::transaction(function () use ($items, $actorUserId, $asignadoAUserId, $asignadoAFuncionarioId, $observacion, $operacionUuid): Collection {
            return collect($items)->map(function (array $item) use ($actorUserId, $asignadoAUserId, $asignadoAFuncionarioId, $observacion, $operacionUuid): InsumoMovimiento {
                $existencia = $this->lockExistencia($item['insumo_id'], $item['sector_id']);

                if ($existencia->cantidad < $item['cantidad']) {
                    throw new DomainException('El stock disponible es insuficiente para completar la asignacion.');
                }

                $stockAnterior = $existencia->cantidad;
                $stockPosterior = $stockAnterior - $item['cantidad'];
                $existencia->update(['cantidad' => $stockPosterior]);

                return InsumoMovimiento::create([
                    'operacion_uuid' => $operacionUuid,
                    'insumo_id' => $item['insumo_id'],
                    'sector_id' => $item['sector_id'],
                    'tipo' => 'asignacion',
                    'cantidad' => $item['cantidad'],
                    'stock_anterior' => $stockAnterior,
                    'stock_posterior' => $stockPosterior,
                    'asignado_a_user_id' => $asignadoAUserId,
                    'asignado_a_funcionario_id' => $asignadoAFuncionarioId,
                    'solicitud_detalle_id' => $item['solicitud_detalle_id'],
                    'created_by_user_id' => $actorUserId,
                    'observacion' => $observacion,
                ]);
            });
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{insumo_id: int, cantidad: int}>  $items
     * @return Collection<int, InsumoMovimiento>
     */
    public function asignarAutomatico(array $items, int $actorUserId, ?int $asignadoAUserId, ?string $observacion = null, ?int $asignadoAFuncionarioId = null): Collection
    {
        $items = $this->normalizeRequestedItems($items);
        $operacionUuid = (string) Str::uuid();

        return DB::transaction(function () use ($items, $actorUserId, $asignadoAUserId, $asignadoAFuncionarioId, $observacion, $operacionUuid): Collection {
            $existencias = InsumoExistencia::query()
                ->whereIn('insumo_id', array_column($items, 'insumo_id'))
                ->where('cantidad', '>', 0)
                ->orderBy('sector_id')
                ->orderBy('insumo_id')
                ->lockForUpdate()
                ->get()
                ->groupBy('insumo_id');
            $movimientos = collect();

            foreach ($items as $item) {
                $disponibles = $existencias->get($item['insumo_id'], collect())->sortBy([
                    ['cantidad', 'desc'],
                    ['sector_id', 'asc'],
                ])->values();

                if ($disponibles->sum('cantidad') < $item['cantidad']) {
                    throw new DomainException('El stock disponible es insuficiente para completar la asignacion.');
                }

                $pendiente = $item['cantidad'];

                foreach ($disponibles as $existencia) {
                    if ($pendiente === 0) {
                        break;
                    }

                    $cantidad = min($pendiente, $existencia->cantidad);
                    $stockAnterior = $existencia->cantidad;
                    $existencia->update(['cantidad' => $stockAnterior - $cantidad]);
                    $pendiente -= $cantidad;

                    $movimientos->push(InsumoMovimiento::create([
                        'operacion_uuid' => $operacionUuid,
                        'insumo_id' => $item['insumo_id'],
                        'sector_id' => $existencia->sector_id,
                        'tipo' => 'asignacion',
                        'cantidad' => $cantidad,
                        'stock_anterior' => $stockAnterior,
                        'stock_posterior' => $stockAnterior - $cantidad,
                        'asignado_a_user_id' => $asignadoAUserId,
                        'asignado_a_funcionario_id' => $asignadoAFuncionarioId,
                        'created_by_user_id' => $actorUserId,
                        'observacion' => $observacion,
                    ]));
                }
            }

            return $movimientos;
        }, attempts: 3);
    }

    public function ajustarImportacion(
        int $insumoId,
        int $sectorId,
        int $cantidadObjetivo,
        int $actorUserId,
        int $importacionId,
        string $operacionUuid,
    ): ?InsumoMovimiento {
        if ($cantidadObjetivo < 0) {
            throw new InvalidArgumentException('La cantidad importada no puede ser negativa.');
        }

        return DB::transaction(function () use ($insumoId, $sectorId, $cantidadObjetivo, $actorUserId, $importacionId, $operacionUuid): ?InsumoMovimiento {
            $existencia = $this->lockExistencia($insumoId, $sectorId, create: true);
            $stockAnterior = $existencia->cantidad;

            if ($stockAnterior === $cantidadObjetivo) {
                return null;
            }

            $existencia->update(['cantidad' => $cantidadObjetivo]);

            return InsumoMovimiento::create([
                'operacion_uuid' => $operacionUuid,
                'insumo_id' => $insumoId,
                'sector_id' => $sectorId,
                'tipo' => 'ajuste_importacion',
                'cantidad' => abs($cantidadObjetivo - $stockAnterior),
                'stock_anterior' => $stockAnterior,
                'stock_posterior' => $cantidadObjetivo,
                'importacion_id' => $importacionId,
                'created_by_user_id' => $actorUserId,
            ]);
        }, attempts: 3);
    }

    private function lockExistencia(int $insumoId, int $sectorId, bool $create = false): InsumoExistencia
    {
        if ($create) {
            $now = now();

            InsumoExistencia::query()->insertOrIgnore([
                'insumo_id' => $insumoId,
                'sector_id' => $sectorId,
                'cantidad' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $existencia = InsumoExistencia::query()
            ->where('insumo_id', $insumoId)
            ->where('sector_id', $sectorId)
            ->lockForUpdate()
            ->first();

        if ($existencia === null) {
            throw new DomainException('No existe stock para el insumo en la bodega seleccionada.');
        }

        return $existencia;
    }

    /**
     * @param  array<int, array{insumo_id: int, sector_id: int, cantidad: int, solicitud_detalle_id?: int|null}>  $items
     * @return array<int, array{insumo_id: int, sector_id: int, cantidad: int, solicitud_detalle_id: int|null}>
     */
    private function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('Debe indicar al menos un insumo para asignar.');
        }

        $normalized = [];

        foreach ($items as $item) {
            $insumoId = $item['insumo_id'] ?? null;
            $sectorId = $item['sector_id'] ?? null;
            $cantidad = $item['cantidad'] ?? null;
            $solicitudDetalleId = $item['solicitud_detalle_id'] ?? null;

            if (! is_int($insumoId) || $insumoId < 1 || ! is_int($sectorId) || $sectorId < 1 || ! is_int($cantidad)) {
                throw new InvalidArgumentException('La asignacion contiene datos invalidos.');
            }

            $this->assertPositiveQuantity($cantidad);

            if ($solicitudDetalleId !== null && (! is_int($solicitudDetalleId) || $solicitudDetalleId < 1)) {
                throw new InvalidArgumentException('El detalle de solicitud es invalido.');
            }

            $key = "{$insumoId}:{$sectorId}";
            if (isset($normalized[$key])) {
                throw new InvalidArgumentException('Un insumo solo puede asignarse una vez por bodega en la misma operacion.');
            }

            $normalized[$key] = [
                'insumo_id' => $insumoId,
                'sector_id' => $sectorId,
                'cantidad' => $cantidad,
                'solicitud_detalle_id' => $solicitudDetalleId,
            ];
        }

        $normalized = array_values($normalized);
        usort($normalized, fn (array $left, array $right): int => [$left['sector_id'], $left['insumo_id']] <=> [$right['sector_id'], $right['insumo_id']]);

        return $normalized;
    }

    /**
     * @param  array<int, array{insumo_id: int, cantidad: int}>  $items
     * @return array<int, array{insumo_id: int, cantidad: int}>
     */
    private function normalizeRequestedItems(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('Debe indicar al menos un insumo para asignar.');
        }

        $normalized = [];

        foreach ($items as $item) {
            $insumoId = $item['insumo_id'] ?? null;
            $cantidad = $item['cantidad'] ?? null;

            if (! is_int($insumoId) || $insumoId < 1 || ! is_int($cantidad)) {
                throw new InvalidArgumentException('La asignacion contiene datos invalidos.');
            }

            $this->assertPositiveQuantity($cantidad);

            if (isset($normalized[$insumoId])) {
                throw new InvalidArgumentException('Un insumo solo puede asignarse una vez en la misma operacion.');
            }

            $normalized[$insumoId] = ['insumo_id' => $insumoId, 'cantidad' => $cantidad];
        }

        return array_values($normalized);
    }

    private function assertPositiveQuantity(int $cantidad): void
    {
        if ($cantidad < 1) {
            throw new InvalidArgumentException('La cantidad debe ser mayor que cero.');
        }
    }
}
