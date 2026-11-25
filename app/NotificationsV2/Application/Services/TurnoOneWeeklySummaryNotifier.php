<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\Turno;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use App\Services\FuncionarioCatalog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TurnoOneWeeklySummaryNotifier
{
    public function __construct(
        private readonly NotificationOrchestrator $orchestrator,
        private readonly FuncionarioCatalog $catalog,
    ) {}

    public function notifyForWeek(CarbonInterface|string|null $date = null): void
    {
        $this->notifyForWeekWithResult($date);
    }

    public function notifyForWeekWithResult(CarbonInterface|string|null $date = null, bool $forceNewDispatch = false): array
    {
        $this->ensureTypeAndTemplate();

        $summary = $this->buildWeekSummaryData($date);

        if ($forceNewDispatch) {
            $summary['options']['idempotency_key'] = 'turno1.manual.'.Str::uuid()->toString();
        }

        $dispatch = $this->orchestrator->dispatch(
            'turno1.resumen_semanal',
            $summary['payload'],
            $summary['options']
        );

        return [
            'dispatch' => $dispatch,
            'skipped' => $summary['skipped'] ?? [],
        ];
    }

    public function buildWeekSummaryData(CarbonInterface|string|null $date = null): array
    {
        $baseDate = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date ?? now());
        $startOfWeek = $baseDate->copy()->startOfWeek();
        $endOfWeek = $startOfWeek->copy()->addDays(4);

        $turno1 = Turno::query()->find(1);

        $juezDespacho = $this->personRow(
            name: (string) ($turno1?->TM1 ?? ''),
            cargo: 'Juez',
        );

        $jefeTurno = $this->personRow(
            name: (string) ($turno1?->TJ1 ?? ''),
            cargo: 'Jefe de Turno',
        );

        $funcionarios = [
            $this->personRow((string) ($turno1?->TF1 ?? ''), (string) ($turno1?->TF1_CARGO ?: 'Funcionario de equipo')),
            $this->personRow((string) ($turno1?->TF2 ?? ''), (string) ($turno1?->TF2_CARGO ?: 'Funcionario de equipo')),
            $this->personRow((string) ($turno1?->TF3 ?? ''), (string) ($turno1?->TF3_CARGO ?: 'Funcionario de equipo')),
        ];

        $funcionarios = collect($funcionarios)
            ->filter(fn (array $item) => trim((string) ($item['nombre'] ?? '')) !== '')
            ->values()
            ->all();

        $semanaRango = $this->resolveSemanaRango($turno1?->FECHA, $startOfWeek, $endOfWeek);

        $payload = $this->buildPayload($baseDate, $semanaRango, $juezDespacho, $jefeTurno, $funcionarios);

        $recipientResolution = $this->resolveWeeklyRecipients($turno1);

        $options = [
            'event_key' => 'turno1.resumen_semanal.'.$startOfWeek->toDateString(),
            'recipients' => $recipientResolution['recipients'],
        ];

        return [
            'payload' => $payload,
            'options' => $options,
            'skipped' => $recipientResolution['skipped'],
        ];
    }

    public function buildPayload(CarbonInterface $baseDate, string $semanaRango, array $juezDespacho, array $jefeTurno, array $funcionarios): array
    {
        $tribunalOral = (string) config('notifications_v2.turno1_summary.tribunal_oral', '4to TOP');

        return [
            'fecha_turno' => $baseDate->toDateString(),
            'semana_rango' => $semanaRango,
            'semana_rango_html' => e($semanaRango),
            'tribunal_oral' => $tribunalOral,
            'tribunal_oral_html' => e($tribunalOral),
            'juez_despacho' => $juezDespacho,
            'juez_despacho_run' => $juezDespacho['run'],
            'juez_despacho_run_html' => e($juezDespacho['run']),
            'juez_despacho_nombre' => $juezDespacho['nombre'],
            'juez_despacho_nombre_html' => e($juezDespacho['nombre']),
            'juez_despacho_telefono' => $juezDespacho['telefono'],
            'juez_despacho_telefono_html' => e($juezDespacho['telefono']),
            'juez_despacho_correo' => $juezDespacho['correo'],
            'juez_despacho_correo_html' => e($juezDespacho['correo']),
            'juez_despacho_cargo' => $juezDespacho['cargo'],
            'juez_despacho_cargo_html' => e($juezDespacho['cargo']),
            'jefe_turno' => $jefeTurno,
            'jefe_turno_run' => $jefeTurno['run'],
            'jefe_turno_run_html' => e($jefeTurno['run']),
            'jefe_turno_nombre' => $jefeTurno['nombre'],
            'jefe_turno_nombre_html' => e($jefeTurno['nombre']),
            'jefe_turno_telefono' => $jefeTurno['telefono'],
            'jefe_turno_telefono_html' => e($jefeTurno['telefono']),
            'jefe_turno_correo' => $jefeTurno['correo'],
            'jefe_turno_correo_html' => e($jefeTurno['correo']),
            'jefe_turno_observaciones' => $jefeTurno['cargo'],
            'jefe_turno_observaciones_html' => e($jefeTurno['cargo']),
            'funcionarios' => $funcionarios,
            'tribunal_rowspan' => max(count($funcionarios), 1) + 4,
            'funcionarios_rows_html' => OutlookEmailTemplates::funcionariosRows($funcionarios),
            'funcionarios_rows_text' => $this->buildFuncionariosRowsText($funcionarios),
        ];
    }

    private function resolveSemanaRango(?string $rawWeekText, Carbon $startOfWeek, Carbon $endOfWeek): string
    {
        $weekText = trim((string) ($rawWeekText ?? ''));
        if ($weekText === '') {
            return $startOfWeek->format('d-m-Y').' al '.$endOfWeek->format('d-m-Y');
        }

        $normalized = preg_replace('/^\s*semana\s+del\s+/i', '', $weekText);
        $normalized = trim((string) $normalized);

        return $normalized !== ''
            ? $normalized
            : $startOfWeek->format('d-m-Y').' al '.$endOfWeek->format('d-m-Y');
    }

    private function personRow(string $name, string $cargo): array
    {
        $trimmed = trim($name);
        $profile = $this->catalog->perfilByNombre($trimmed);

        return [
            'run' => (string) ($profile['run'] ?? '-'),
            'nombre' => $trimmed !== '' ? $trimmed : '-',
            'telefono' => (string) ($profile['telefono'] ?? '-'),
            'correo' => (string) ($profile['email'] ?? '-'),
            'cargo' => $cargo,
        ];
    }

    private function buildFuncionariosRowsText(array $funcionarios): string
    {
        if ($funcionarios === []) {
            return 'Sin funcionarios asignados.';
        }

        return collect($funcionarios)->map(function (array $item): string {
            return sprintf(
                '%s | %s | %s | %s | %s',
                (string) ($item['run'] ?? '-'),
                (string) ($item['nombre'] ?? '-'),
                (string) ($item['telefono'] ?? '-'),
                (string) ($item['correo'] ?? '-'),
                (string) ($item['cargo'] ?? '-'),
            );
        })->implode(' || ');
    }

    private function resolveWeeklyRecipients(?Turno $turno1): array
    {
        if (! $turno1) {
            return ['recipients' => [], 'skipped' => []];
        }

        $recipients = $this->catalogRecipientsWithoutMagistrados();
        $skipped = [];

        $magistradosSeleccionados = [
            'TM1' => (string) ($turno1->TM1 ?? ''),
            'TM2' => (string) ($turno1->TM2 ?? ''),
            'TM3' => (string) ($turno1->TM3 ?? ''),
        ];

        foreach ($magistradosSeleccionados as $slot => $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $profile = $this->catalog->destinatarioByNombre($name);
            $email = strtolower(trim((string) ($profile['email'] ?? '')));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('turno1 summary recipient without valid email.', [
                    'slot' => $slot,
                    'name' => $name,
                ]);

                $skipped[] = [
                    'slot' => $slot,
                    'name' => $name,
                    'reason' => 'sin_correo_valido',
                ];

                continue;
            }

            $recipients[] = [
                'type' => 'to',
                'name' => $profile['nombre'] ?? $name,
                'email' => $email,
                'context' => 'turno1.resumen_semanal.magistrado.'.$slot,
            ];
        }

        $uniqueRecipients = collect($recipients)
            ->map(fn (array $recipient): array => [
                'type' => $recipient['type'] ?? 'to',
                'name' => $recipient['name'] ?? null,
                'email' => $recipient['email'] ?? null,
                'context' => $recipient['context'] ?? 'turno1.resumen_semanal.selected',
            ])
            ->filter(fn (array $recipient) => is_string($recipient['email']) && $recipient['email'] !== '')
            ->unique(fn (array $recipient) => strtolower($recipient['email']))
            ->values()
            ->all();

        return [
            'recipients' => $uniqueRecipients,
            'skipped' => $skipped,
        ];
    }

    private function catalogRecipientsWithoutMagistrados(): array
    {
        $roles = collect($this->catalog->roles())
            ->reject(fn (string $role): bool => $role === 'juez')
            ->values()
            ->all();

        return collect($this->catalog->destinatariosByRoles($roles))
            ->map(function (array $recipient): ?array {
                $email = strtolower(trim((string) ($recipient['email'] ?? '')));

                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    return null;
                }

                return [
                    'type' => 'to',
                    'name' => $recipient['nombre'] ?? null,
                    'email' => $email,
                    'context' => 'turno1.resumen_semanal.catalogo',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'turno1.resumen_semanal'],
            [
                'name' => 'Resumen semanal turno 1',
                'description' => 'Notificacion semanal con la tabla completa de Turno 1.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'high',
                'copy_contexts' => null,
            ]
        );

        OutlookEmailTemplates::ensureActive($type);
    }
}
