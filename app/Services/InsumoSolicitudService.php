<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\InsumoSolicitud;
use App\Models\User;
use App\NotificationsV2\Application\Services\InsumosNotifier;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InsumoSolicitudService
{
    public function __construct(private readonly InsumosNotifier $notifier) {}

    /**
     * @param  array<int, array{insumo_id: int, cantidad: int}>  $items
     */
    public function crear(array $items, int $solicitanteUserId, ?string $observacion = null, ?string $firma = null): InsumoSolicitud
    {
        $items = $this->normalizeItems($items);
        $firma = $this->validateFirma($firma, $solicitanteUserId);

        return DB::transaction(function () use ($items, $solicitanteUserId, $observacion, $firma): InsumoSolicitud {
            $insumos = Insumo::query()
                ->where('activo', true)
                ->whereIn('id', array_column($items, 'insumo_id'))
                ->pluck('id')
                ->all();

            if (count($insumos) !== count($items)) {
                throw new DomainException('Uno o mas insumos ya no estan disponibles para solicitar.');
            }

            $solicitud = InsumoSolicitud::create([
                'solicitante_user_id' => $solicitanteUserId,
                'estado' => 'pendiente',
                'observacion' => $observacion,
                'firma' => $firma,
            ]);

            $solicitud->detalles()->createMany($items);

            $solicitud->load('solicitante', 'detalles.insumo');
            $this->notifier->solicitudCreada($solicitud);

            return $solicitud;
        }, attempts: 3);
    }

    public function cancelar(InsumoSolicitud $solicitud, int $solicitanteUserId): InsumoSolicitud
    {
        return DB::transaction(function () use ($solicitud, $solicitanteUserId): InsumoSolicitud {
            $solicitud = InsumoSolicitud::query()->lockForUpdate()->findOrFail($solicitud->id);

            if ($solicitud->solicitante_user_id !== $solicitanteUserId) {
                throw new AuthorizationException('No puede cancelar una solicitud ajena.');
            }

            if (! in_array($solicitud->estado, ['pendiente', 'parcial'], true)) {
                throw new DomainException('La solicitud ya no puede cancelarse.');
            }

            $solicitud->update(['estado' => 'cancelada']);

            return $solicitud;
        }, attempts: 3);
    }

    /**
     * @param  array<int, array{insumo_id: int, cantidad: int}>  $items
     * @return array<int, array{insumo_id: int, cantidad: int}>
     */
    private function normalizeItems(array $items): array
    {
        if ($items === []) {
            throw new InvalidArgumentException('Debe agregar al menos un insumo a la solicitud.');
        }

        $normalized = [];

        foreach ($items as $item) {
            $insumoId = $item['insumo_id'] ?? null;
            $cantidad = $item['cantidad'] ?? null;

            if (! is_int($insumoId) || $insumoId < 1 || ! is_int($cantidad) || $cantidad < 1) {
                throw new InvalidArgumentException('La solicitud contiene cantidades invalidas.');
            }

            if (isset($normalized[$insumoId])) {
                throw new InvalidArgumentException('Un insumo solo puede agregarse una vez a la solicitud.');
            }

            $normalized[$insumoId] = ['insumo_id' => $insumoId, 'cantidad_solicitada' => $cantidad];
        }

        return array_values($normalized);
    }

    private function validateFirma(?string $firma, int $solicitanteUserId): string
    {
        $solicitante = User::query()->findOrFail($solicitanteUserId);
        $firma = trim($firma ?: $solicitante->name);
        $firmasPermitidas = array_merge([$solicitante->name], array_map(fn (int $sala): string => "Sala {$sala}", [701, 801, 802, 803, 901, 902, 903]));

        if (! in_array($firma, $firmasPermitidas, true)) {
            throw new InvalidArgumentException('La firma seleccionada no es valida.');
        }

        return $firma;
    }
}
