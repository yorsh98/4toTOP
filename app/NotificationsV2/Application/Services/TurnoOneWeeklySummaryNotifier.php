<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\Turno;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\DefaultEmailTemplates;
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
        $turno2 = Turno::query()->find(2);

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

        $semanaRango = $this->resolveSemanaRango($turno2?->FECHA, $startOfWeek, $endOfWeek);

        $payload = [
            'fecha_turno' => $baseDate->toDateString(),
            'semana_rango' => $semanaRango,
            'tribunal_oral' => (string) config('notifications_v2.turno1_summary.tribunal_oral', '4to TOP'),
            'juez_despacho' => $juezDespacho,
            'juez_despacho_run' => $juezDespacho['run'],
            'juez_despacho_nombre' => $juezDespacho['nombre'],
            'juez_despacho_telefono' => $juezDespacho['telefono'],
            'juez_despacho_correo' => $juezDespacho['correo'],
            'juez_despacho_cargo' => $juezDespacho['cargo'],
            'jefe_turno' => $jefeTurno,
            'jefe_turno_run' => $jefeTurno['run'],
            'jefe_turno_nombre' => $jefeTurno['nombre'],
            'jefe_turno_telefono' => $jefeTurno['telefono'],
            'jefe_turno_correo' => $jefeTurno['correo'],
            'jefe_turno_observaciones' => $jefeTurno['cargo'],
            'funcionarios' => $funcionarios,
            'tribunal_rowspan' => max(count($funcionarios), 1) + 4,
            'funcionarios_rows_html' => $this->buildFuncionariosRowsHtml($funcionarios),
            'funcionarios_rows_text' => $this->buildFuncionariosRowsText($funcionarios),
        ];

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

    private function buildFuncionariosRowsHtml(array $funcionarios): string
    {
        if ($funcionarios === []) {
            return '<tr><td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;" colspan="5">Sin funcionarios asignados.</td></tr>';
        }

        $rows = '';

        foreach ($funcionarios as $funcionario) {
            $correo = (string) ($funcionario['correo'] ?? '-');
            $correoHtml = $correo !== '-' ? '<a href="mailto:'.e($correo).'" style="color:#1d4ed8;">'.e($correo).'</a>' : '-';

            $rows .= '<tr>'
                .'<td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">'.e((string) ($funcionario['run'] ?? '-')).'</td>'
                .'<td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;font-size:16px;line-height:20px;">'.e((string) ($funcionario['nombre'] ?? '-')).'</td>'
                .'<td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">'.e((string) ($funcionario['telefono'] ?? '-')).'</td>'
                .'<td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">'.$correoHtml.'</td>'
                .'<td style="border:1px solid #cbd5e1;padding:8px;text-align:center;background:#ffffff;">'.e((string) ($funcionario['cargo'] ?? '-')).'</td>'
                .'</tr>';
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

        $activeTemplate = NotificationTemplate::query()
            ->where('notification_type_id', $type->id)
            ->where('is_active', true)
            ->first();

        if ($activeTemplate) {
            $activeTemplate->update([
                'subject' => 'Turno Semanal - Semana {{ semana_rango }}',
                'body_html' => DefaultEmailTemplates::turno1ResumenSemanalHtml(),
                'body_text' => DefaultEmailTemplates::turno1ResumenSemanalText(),
                'variables_schema' => [
                    'fecha_turno',
                    'semana_rango',
                    'tribunal_oral',
                    'juez_despacho_run',
                    'juez_despacho_nombre',
                    'juez_despacho_telefono',
                    'juez_despacho_correo',
                    'juez_despacho_cargo',
                    'jefe_turno_run',
                    'jefe_turno_nombre',
                    'jefe_turno_telefono',
                    'jefe_turno_correo',
                    'jefe_turno_observaciones',
                    'tribunal_rowspan',
                    'funcionarios_rows_html',
                    'funcionarios_rows_text',
                ],
            ]);

            return;
        }

        NotificationTemplate::query()->create([
            'notification_type_id' => $type->id,
            'version' => 1,
            'subject' => 'Turno Semanal - Semana {{ semana_rango }}',
            'body_html' => DefaultEmailTemplates::turno1ResumenSemanalHtml(),
            'body_text' => DefaultEmailTemplates::turno1ResumenSemanalText(),
            'variables_schema' => [
                'fecha_turno',
                'semana_rango',
                'tribunal_oral',
                'juez_despacho_run',
                'juez_despacho_nombre',
                'juez_despacho_telefono',
                'juez_despacho_correo',
                'juez_despacho_cargo',
                'jefe_turno_run',
                'jefe_turno_nombre',
                'jefe_turno_telefono',
                'jefe_turno_correo',
                'jefe_turno_observaciones',
                'tribunal_rowspan',
                'funcionarios_rows_html',
                'funcionarios_rows_text',
            ],
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }
}
