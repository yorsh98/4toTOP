<?php

namespace App\Services;

use App\Models\Funcionario;
use App\Models\InsumoMovimiento;
use App\Models\InsumoSolicitud;
use App\Models\InsumoSolicitudDetalle;
use App\Models\User;
use App\NotificationsV2\Application\Services\InsumosNotifier;
use DomainException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InsumoGestionService
{
    public function __construct(
        private readonly InsumoStockService $stockService,
        private readonly InsumosNotifier $notifier,
    ) {}

    /**
     * @param  array<int, array{solicitud_detalle_id: int, sector_id: int, cantidad: int}>  $entregas
     * @return Collection<int, InsumoMovimiento>
     */
    public function entregar(InsumoSolicitud $solicitud, array $entregas, int $actorUserId): Collection
    {
        $entregas = $this->normalizeEntregas($entregas);

        return DB::transaction(function () use ($solicitud, $entregas, $actorUserId): Collection {
            $solicitud = InsumoSolicitud::query()->lockForUpdate()->findOrFail($solicitud->id);

            if (! in_array($solicitud->estado, ['pendiente', 'parcial'], true)) {
                throw new DomainException('La solicitud ya no admite entregas.');
            }

            $detalles = InsumoSolicitudDetalle::query()
                ->where('insumo_solicitud_id', $solicitud->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $entregados = InsumoMovimiento::query()
                ->whereIn('solicitud_detalle_id', $detalles->keys())
                ->where('tipo', 'asignacion')
                ->selectRaw('solicitud_detalle_id, SUM(cantidad) as cantidad')
                ->groupBy('solicitud_detalle_id')
                ->pluck('cantidad', 'solicitud_detalle_id');
            $items = [];

            foreach ($entregas as $entrega) {
                $detalle = $detalles->get($entrega['solicitud_detalle_id']);
                if ($detalle === null) {
                    throw new DomainException('La entrega contiene un detalle que no pertenece a la solicitud.');
                }

                $pendiente = $detalle->cantidad_solicitada - (int) ($entregados[$detalle->id] ?? 0);
                if ($entrega['cantidad'] > $pendiente) {
                    throw new DomainException('La cantidad entregada supera lo pendiente de la solicitud.');
                }

                $items[] = [
                    'insumo_id' => $detalle->insumo_id,
                    'sector_id' => $entrega['sector_id'],
                    'cantidad' => $entrega['cantidad'],
                    'solicitud_detalle_id' => $detalle->id,
                ];
                $entregados[$detalle->id] = (int) ($entregados[$detalle->id] ?? 0) + $entrega['cantidad'];
            }

            $movimientos = $this->stockService->asignar($items, $actorUserId, $solicitud->solicitante_user_id);
            $completada = $detalles->every(fn (InsumoSolicitudDetalle $detalle): bool => (int) ($entregados[$detalle->id] ?? 0) >= $detalle->cantidad_solicitada);
            $solicitud->update(['estado' => $completada ? 'completada' : 'parcial']);
            $asignadoA = User::query()->findOrFail($solicitud->solicitante_user_id);
            $this->notifier->asignacionRegistrada($movimientos, $asignadoA);
            $this->notifier->stockBajo($movimientos);

            return $movimientos;
        }, attempts: 3);
    }

    public function rechazar(InsumoSolicitud $solicitud, int $actorUserId, string $motivo): InsumoSolicitud
    {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new InvalidArgumentException('Debe indicar un motivo de rechazo.');
        }

        return DB::transaction(function () use ($solicitud, $actorUserId, $motivo): InsumoSolicitud {
            $solicitud = InsumoSolicitud::query()->lockForUpdate()->findOrFail($solicitud->id);

            if ($solicitud->estado !== 'pendiente') {
                throw new DomainException('Solo se pueden rechazar solicitudes pendientes.');
            }

            $solicitud->update([
                'estado' => 'rechazada',
                'rechazada_por_user_id' => $actorUserId,
                'motivo_rechazo' => $motivo,
            ]);

            return $solicitud;
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{insumo_id: int, sector_id: int, cantidad: int}>  $items
     * @return Collection<int, InsumoMovimiento>
     */
    public function asignarDirecto(array $items, int $actorUserId, int $asignadoAFuncionarioId, ?string $observacion = null): Collection
    {
        return DB::transaction(function () use ($items, $actorUserId, $asignadoAFuncionarioId, $observacion): Collection {
            $recipient = Funcionario::query()->findOrFail($asignadoAFuncionarioId);
            $recipientUserId = User::query()
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($recipient->email)])
                ->value('id');

            $movimientos = $this->stockService->asignarAutomatico($items, $actorUserId, $recipientUserId, $observacion, $recipient->id);
            $this->notifier->asignacionRegistrada($movimientos, $recipient, false);
            $this->notifier->stockBajo($movimientos);

            return $movimientos;
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{solicitud_detalle_id: int, sector_id: int, cantidad: int}>  $entregas
     * @return array<int, array{solicitud_detalle_id: int, sector_id: int, cantidad: int}>
     */
    private function normalizeEntregas(array $entregas): array
    {
        if ($entregas === []) {
            throw new InvalidArgumentException('Debe indicar al menos una entrega.');
        }

        $normalized = [];

        foreach ($entregas as $entrega) {
            $detalleId = $entrega['solicitud_detalle_id'] ?? null;
            $sectorId = $entrega['sector_id'] ?? null;
            $cantidad = $entrega['cantidad'] ?? null;

            if (! is_int($detalleId) || $detalleId < 1 || ! is_int($sectorId) || $sectorId < 1 || ! is_int($cantidad) || $cantidad < 1) {
                throw new InvalidArgumentException('La entrega contiene datos invalidos.');
            }

            if (isset($normalized[$detalleId])) {
                throw new InvalidArgumentException('Cada detalle solo puede entregarse una vez por operacion.');
            }

            $normalized[$detalleId] = [
                'solicitud_detalle_id' => $detalleId,
                'sector_id' => $sectorId,
                'cantidad' => $cantidad,
            ];
        }

        return array_values($normalized);
    }
}
