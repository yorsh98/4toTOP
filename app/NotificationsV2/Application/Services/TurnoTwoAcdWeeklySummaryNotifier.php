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

class TurnoTwoAcdWeeklySummaryNotifier
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
            $summary['options']['idempotency_key'] = 'turno2.acd.manual.'.Str::uuid()->toString();
        }

        $dispatch = $this->orchestrator->dispatch(
            'turno2.acd.resumen_semanal',
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

        $turno2 = Turno::query()->find(2);

        $magistrados = [
            $this->personRow((string) ($turno2?->ACDM1 ?? ''), 'Magistrado 1'),
            $this->personRow((string) ($turno2?->ACDM2 ?? ''), 'Magistrado 2'),
            $this->personRow((string) ($turno2?->ACDM3 ?? ''), 'Magistrado 3'),
        ];

        $jefatura = $this->personRow((string) ($turno2?->ACDJ1 ?? ''), 'Jefe de Turno');

        $funcionarios = [
            $this->personRow((string) ($turno2?->ACDF1 ?? ''), (string) ($turno2?->ACDF1_CARGO ?: 'Funcion de equipo 1')),
            $this->personRow((string) ($turno2?->ACDF2 ?? ''), (string) ($turno2?->ACDF2_CARGO ?: 'Funcion de equipo 2')),
            $this->personRow((string) ($turno2?->ACDF3 ?? ''), (string) ($turno2?->ACDF3_CARGO ?: 'Funcion de equipo 3')),
        ];

        $funcionarios = collect($funcionarios)
            ->filter(fn (array $item) => trim((string) ($item['nombre'] ?? '')) !== '')
            ->values()
            ->all();

        $tribunalOral = (string) config('notifications_v2.turno1_summary.tribunal_oral', '4to TOP');

        $semanaRango = $this->resolveSemanaRango($turno2?->FECHA, $startOfWeek, $endOfWeek);

        $payload = $this->buildPayload($baseDate, $semanaRango, $tribunalOral, $magistrados, $jefatura, $funcionarios);

        $recipientResolution = $this->resolveWeeklyRecipients($turno2);

        $options = [
            'event_key' => 'turno2.acd.resumen_semanal.'.$startOfWeek->toDateString(),
            'recipients' => $recipientResolution['recipients'],
        ];

        return [
            'payload' => $payload,
            'options' => $options,
            'skipped' => $recipientResolution['skipped'],
        ];
    }

    public function buildPayload(CarbonInterface $baseDate, string $semanaRango, string $tribunalOral, array $magistrados, array $jefatura, array $funcionarios): array
    {
        $magistradoRows = $magistrados === [] ? 3 : count($magistrados);
        $tribunalRowspan = $magistradoRows + 3 + max(count($funcionarios), 1);

        return [
            'fecha_turno' => $baseDate->toDateString(),
            'semana_rango' => $semanaRango,
            'semana_rango_html' => e($semanaRango),
            'tribunal_oral' => $tribunalOral,
            'tribunal_oral_html' => e($tribunalOral),
            'magistrados' => $magistrados,
            'magistrados_rows_html' => $this->buildMagistradosRowsHtml($magistrados, $tribunalOral, $tribunalRowspan),
            'jefatura_run' => $jefatura['run'],
            'jefatura_run_html' => e($jefatura['run']),
            'jefatura_nombre' => $jefatura['nombre'],
            'jefatura_nombre_html' => e($jefatura['nombre']),
            'jefatura_telefono' => $jefatura['telefono'],
            'jefatura_telefono_html' => e($jefatura['telefono']),
            'jefatura_correo' => $jefatura['correo'],
            'jefatura_correo_html' => e($jefatura['correo']),
            'jefatura_observaciones' => $jefatura['cargo'],
            'jefatura_observaciones_html' => e($jefatura['cargo']),
            'funcionarios' => $funcionarios,
            'tribunal_rowspan' => $tribunalRowspan,
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

    private function buildMagistradosRowsHtml(array $magistrados, string $tribunalOral, int $tribunalRowspan): string
    {
        $fallback = [
            ['run' => '-', 'nombre' => '-', 'telefono' => '-', 'correo' => '-', 'cargo' => 'Magistrado 1'],
            ['run' => '-', 'nombre' => '-', 'telefono' => '-', 'correo' => '-', 'cargo' => 'Magistrado 2'],
            ['run' => '-', 'nombre' => '-', 'telefono' => '-', 'correo' => '-', 'cargo' => 'Magistrado 3'],
        ];

        if ($magistrados === []) {
            $magistrados = $fallback;
        }

        $rows = '';

        foreach ($magistrados as $index => $magistrado) {
            $rows .= OutlookEmailTemplates::personRow($magistrado, $index === 0 ? $tribunalOral : null, $tribunalRowspan);
        }

        return $rows;
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

    private function resolveWeeklyRecipients(?Turno $turno2): array
    {
        if (! $turno2) {
            return ['recipients' => [], 'skipped' => []];
        }

        $recipients = $this->catalogRecipientsWithoutMagistrados();

        $magistradosSeleccionados = [
            'ACDM1' => (string) ($turno2->ACDM1 ?? ''),
            'ACDM2' => (string) ($turno2->ACDM2 ?? ''),
            'ACDM3' => (string) ($turno2->ACDM3 ?? ''),
        ];

        $skipped = [];

        foreach ($magistradosSeleccionados as $slot => $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $profile = $this->catalog->destinatarioByNombre($name);
            $email = strtolower(trim((string) ($profile['email'] ?? '')));

            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('turno2 acd summary recipient without valid email.', [
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
                'context' => 'turno2.acd.resumen_semanal.magistrado.'.$slot,
            ];
        }

        $uniqueRecipients = collect($recipients)
            ->map(fn (array $recipient): array => [
                'type' => $recipient['type'] ?? 'to',
                'name' => $recipient['name'] ?? null,
                'email' => $recipient['email'] ?? null,
                'context' => $recipient['context'] ?? 'turno2.acd.resumen_semanal.selected',
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
                    'context' => 'turno2.acd.resumen_semanal.catalogo',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'turno2.acd.resumen_semanal'],
            [
                'name' => 'Resumen turno 2 ACD trimestral',
                'description' => 'Notificacion por periodo con la tabla completa de Turno 2 ACD.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'high',
                'copy_contexts' => null,
            ]
        );

        OutlookEmailTemplates::ensureActive($type);
    }
}
