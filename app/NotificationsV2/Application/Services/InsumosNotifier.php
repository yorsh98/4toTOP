<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use App\Models\InsumoSolicitud;
use App\Models\User;
use App\Notifications\InsumosServiciosNotification;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class InsumosNotifier
{
    public function __construct(private readonly NotificationOrchestrator $orchestrator) {}

    public function solicitudCreada(InsumoSolicitud $solicitud): void
    {
        $solicitud->loadMissing('solicitante', 'detalles.insumo');
        $eventKey = "insumo.solicitado.{$solicitud->id}";
        $items = $solicitud->detalles->map(fn ($detalle): string => "{$detalle->insumo->nombre}: {$detalle->cantidad_solicitada} {$detalle->insumo->unidad_medida}");
        $payload = $this->payload(
            'Nueva solicitud de insumos',
            "{$solicitud->solicitante->name} solicito insumos.",
            $items,
            $solicitud->firma,
        );

        $services = $this->serviciosUsers();
        $this->dispatchMail('insumo.solicitado', $eventKey, $payload, $this->withValidEmail($services));
        $this->notifyDatabase($services, $eventKey, 'Nueva solicitud de insumos', $payload['line'], '/insumos/gestion');
    }

    /** @param Collection<int, InsumoMovimiento> $movimientos */
    public function asignacionRegistrada(Collection $movimientos, User|Funcionario $asignadoA, bool $notificarAsignado = true): void
    {
        if ($movimientos->isEmpty()) {
            return;
        }

        $movimientos->each(fn (InsumoMovimiento $movimiento) => $movimiento->loadMissing('insumo'));
        $eventKey = "insumo.asignado.{$movimientos->first()->operacion_uuid}";
        $items = $movimientos
            ->groupBy('insumo_id')
            ->map(fn (Collection $items): string => "{$items->first()->insumo->nombre}: {$items->sum('cantidad')} {$items->first()->insumo->unidad_medida}")
            ->values();
        $payload = $this->payload(
            'Asignacion de insumos',
            'Se asignaron insumos a '.($asignadoA instanceof Funcionario ? $asignadoA->nombre : $asignadoA->name).'.',
            $items,
        );
        $services = $this->serviciosUsers();
        $mailRecipients = $services->when($notificarAsignado, fn (Collection $users) => $users->concat([$asignadoA]))
            ->filter(fn (User|Funcionario $recipient): bool => filter_var($recipient->email, FILTER_VALIDATE_EMAIL) !== false)
            ->unique(fn (User|Funcionario $recipient): string => strtolower($recipient->email))
            ->values();

        $this->dispatchMail('insumo.asignado', $eventKey, $payload, $mailRecipients);
        $this->notifyDatabase($services, $eventKey, 'Asignacion de insumos', $payload['line'], '/insumos/gestion');
    }

    /** @param Collection<int, InsumoMovimiento> $movimientos */
    public function stockBajo(Collection $movimientos): void
    {
        if ($movimientos->isEmpty()) {
            return;
        }

        $movimientosPorInsumo = $movimientos->groupBy('insumo_id');
        $insumos = Insumo::query()->whereIn('id', $movimientosPorInsumo->keys())->get()->keyBy('id');
        $stockActual = InsumoExistencia::query()
            ->whereIn('insumo_id', $movimientosPorInsumo->keys())
            ->selectRaw('insumo_id, SUM(cantidad) as cantidad')
            ->groupBy('insumo_id')
            ->pluck('cantidad', 'insumo_id');
        $items = collect();

        foreach ($movimientosPorInsumo as $insumoId => $movimientosInsumo) {
            $insumo = $insumos->get($insumoId);
            $actual = (int) ($stockActual[$insumoId] ?? 0);
            $anterior = $actual + $movimientosInsumo->sum('cantidad');

            if ($insumo !== null && $anterior > $insumo->stock_minimo && $actual <= $insumo->stock_minimo) {
                $items->push("{$insumo->nombre}: quedan {$actual} {$insumo->unidad_medida} (minimo {$insumo->stock_minimo})");
            }
        }

        if ($items->isEmpty()) {
            return;
        }

        $eventKey = "insumo.stock_bajo.{$movimientos->first()->operacion_uuid}";
        $payload = $this->payload('Stock bajo de insumos', 'Uno o mas insumos alcanzaron el stock minimo.', $items);
        $services = $this->serviciosUsers();

        $this->dispatchMail('insumo.stock_bajo', $eventKey, $payload, $this->withValidEmail($services));
        $this->notifyDatabase($services, $eventKey, 'Stock bajo de insumos', $payload['line'], '/insumos/inventario');
    }

    /** @return Collection<int, User> */
    private function serviciosUsers(): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('name', 'servicios'))
            ->whereNotNull('approved_at')
            ->whereNull('rejected_at')
            ->whereNull('suspended_at')
            ->get();
    }

    private function withValidEmail(Collection $recipients): Collection
    {
        return $recipients->filter(fn (User $user): bool => filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false)->values();
    }

    /** @param Collection<int, User|Funcionario> $recipients */
    private function dispatchMail(string $typeCode, string $eventKey, array $payload, Collection $recipients): void
    {
        if (! config('notifications_v2.enabled', false)) {
            return;
        }

        $this->ensureTypeAndTemplate($typeCode);
        $this->orchestrator->dispatch($typeCode, $payload, [
            'event_key' => $eventKey,
            'idempotency_key' => hash('sha256', $eventKey),
            'recipients' => $recipients->map(fn (User|Funcionario $recipient): array => [
                'type' => 'to',
                'name' => $recipient instanceof Funcionario ? $recipient->nombre : $recipient->name,
                'email' => $recipient->email,
                'context' => 'insumos',
            ])->all(),
        ]);
    }

    /** @param Collection<int, User> $users */
    private function notifyDatabase(Collection $users, string $eventKey, string $title, string $message, string $url): void
    {
        foreach ($users as $user) {
            if ($user->notifications()
                ->where('type', InsumosServiciosNotification::class)
                ->whereJsonContains('data->event_key', $eventKey)
                ->exists()) {
                continue;
            }

            $notification = new InsumosServiciosNotification($eventKey, $title, $message, $url);
            $notification->id = Uuid::uuid5(Uuid::NAMESPACE_URL, "4totop:{$user->getKey()}|{$eventKey}")->toString();

            try {
                $user->notify($notification);
            } catch (UniqueConstraintViolationException $exception) {
                // La notificacion ya fue creada por otra solicitud.
            }
        }
    }

    /** @param Collection<int, string> $items */
    public function payload(string $title, string $line, Collection $items, ?string $firma = null): array
    {
        return [
            'title' => $title,
            'line' => $line,
            'line_html' => e($line),
            'items_html' => $items->map(fn (string $item): string => '<li>'.e($item).'</li>')->implode(''),
            'items_text' => $items->implode('; '),
            'firma_html' => $firma ? '<p style="margin:20px 0 0;">'.e($firma).'</p>' : '',
            'firma_text' => $firma ?? '',
        ];
    }

    private function ensureTypeAndTemplate(string $typeCode): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => $typeCode],
            [
                'name' => match ($typeCode) {
                    'insumo.solicitado' => 'Solicitud de insumos',
                    'insumo.asignado' => 'Asignacion de insumos',
                    default => 'Stock bajo de insumos',
                },
                'description' => 'Notificacion operativa del modulo Insumos.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => $typeCode === 'insumo.stock_bajo' ? 'high' : 'normal',
                'copy_contexts' => null,
            ],
        );

        OutlookEmailTemplates::ensureActive($type);
    }
}
