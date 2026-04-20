<?php

namespace App\NotificationsV2\Application\Services;

use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\DefaultEmailTemplates;
use App\Services\FuncionarioCatalog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TurnoAssignmentNotifier
{
    public function __construct(
        private readonly NotificationOrchestrator $orchestrator,
        private readonly FuncionarioCatalog $catalog,
    ) {}

    public function notifyChanges(string $bloqueTurno, array $before, array $after, ?string $fechaTurno = null): void
    {
        $this->ensureTurnoTypeAndTemplate();

        $fecha = trim((string) ($fechaTurno ?? ''));
        if ($fecha === '') {
            $fecha = now()->toDateString();
        }

        foreach ($this->changedSlots($before, $after) as $slot => $nombreAsignado) {
            $payload = [
                'fecha_turno' => $fecha,
                'bloque_turno' => $bloqueTurno,
                'slot_turno' => $slot,
                'nombre_asignado' => $nombreAsignado,
            ];

            $recipient = $this->catalog->destinatarioByNombre($nombreAsignado);

            $options = [
                'event_key' => sprintf(
                    'turno.asignado.%s.%s.%s',
                    Str::slug($bloqueTurno),
                    strtolower($slot),
                    $fecha
                ),
            ];

            if ($recipient) {
                $options['recipients'] = [[
                    'type' => 'to',
                    'name' => $recipient['nombre'],
                    'email' => $recipient['email'],
                    'context' => 'turno.asignado.destinatario',
                ]];
            } else {
                Log::warning('Turno assignment notification skipped recipient resolution.', [
                    'event' => 'turno.asignado',
                    'bloque_turno' => $bloqueTurno,
                    'slot_turno' => $slot,
                    'nombre_asignado' => $nombreAsignado,
                ]);
            }

            $this->orchestrator->dispatch('turno.asignado', $payload, $options);
        }
    }

    private function changedSlots(array $before, array $after): array
    {
        $changed = [];

        foreach ($after as $slot => $value) {
            if ($slot === 'FECHA' || str_ends_with($slot, '_CARGO')) {
                continue;
            }

            $newValue = trim((string) $value);
            $oldValue = trim((string) ($before[$slot] ?? ''));

            if ($newValue === '' || $newValue === $oldValue) {
                continue;
            }

            $changed[$slot] = $newValue;
        }

        return $changed;
    }

    private function ensureTurnoTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'turno.asignado'],
            [
                'name' => 'Turno asignado',
                'description' => 'Notificacion enviada cuando se asigna o actualiza un turno.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'normal',
                'copy_contexts' => null,
            ]
        );

        $activeTemplate = NotificationTemplate::query()
            ->where('notification_type_id', $type->id)
            ->where('is_active', true)
            ->first();

        if ($activeTemplate) {
            return;
        }

        NotificationTemplate::query()->create([
            'notification_type_id' => $type->id,
            'version' => 1,
            'subject' => 'Asignacion de turno {{ slot_turno }} - {{ fecha_turno }}',
            'body_html' => DefaultEmailTemplates::turnoAsignadoHtml(),
            'body_text' => DefaultEmailTemplates::turnoAsignadoText(),
            'variables_schema' => [
                'fecha_turno',
                'bloque_turno',
                'slot_turno',
                'nombre_asignado',
            ],
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }
}
