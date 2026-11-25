<?php

namespace App\NotificationsV2\Application\Services;

use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use App\Services\DailyAusentismoSnapshotService;
use App\Services\FuncionarioCatalog;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class DailyAusentismoSummaryNotifier
{
    public function __construct(
        private readonly NotificationOrchestrator $orchestrator,
        private readonly DailyAusentismoSnapshotService $snapshotService,
        private readonly FuncionarioCatalog $funcionarioCatalog,
    ) {}

    public function notifyForDate(CarbonInterface|string|null $date = null, bool $force = false): void
    {
        $this->ensureAusentismoTypeAndTemplate();

        $snapshot = $this->snapshotService->forDate($date);
        $fechaReporte = (string) ($snapshot['fecha_reporte'] ?? now()->toDateString());
        $ausentes = is_array($snapshot['ausentes'] ?? null) ? $snapshot['ausentes'] : [];
        $totalAusentes = (int) ($snapshot['total_ausentes'] ?? count($ausentes));
        $sendWhenEmpty = (bool) config('notifications_v2.ausentismo.send_when_empty', true);

        if ($totalAusentes === 0 && ! $sendWhenEmpty) {
            return;
        }

        $payload = $this->buildPayload($fechaReporte, $ausentes, $totalAusentes);

        $options = [
            'event_key' => 'ausentismo.resumen_diario.'.$fechaReporte,
            'recipients' => $this->catalogRecipientsWithoutJueces(),
            'idempotency_key' => ($force ? 'force.' : 'run.').'ausentismo.resumen_diario.'.$fechaReporte.'.'.Str::uuid()->toString(),
        ];

        $this->orchestrator->dispatch('ausentismo.resumen_diario', $payload, $options);
    }

    public function buildPayload(string $fechaReporte, array $ausentes, int $totalAusentes): array
    {
        return [
            'fecha_reporte' => $fechaReporte,
            'total_ausentes' => $totalAusentes,
            'ausentes' => $ausentes,
            'ausentes_html' => $this->buildAusentesHtml($ausentes),
            'ausentes_text' => $this->buildAusentesText($ausentes),
            'mensaje_sin_novedades' => $totalAusentes === 0
                ? (string) config('notifications_v2.ausentismo.empty_message', 'no hay ausencias registradas')
                : '',
        ];
    }

    private function catalogRecipientsWithoutJueces(): array
    {
        $roles = array_values(array_filter(
            $this->funcionarioCatalog->roles(),
            static fn (string $role): bool => $role !== 'juez',
        ));

        return array_map(static function (array $recipient): array {
            return [
                'type' => 'to',
                'name' => $recipient['nombre'] ?? null,
                'email' => $recipient['email'] ?? null,
                'context' => 'all_catalog_without_juez',
            ];
        }, $this->funcionarioCatalog->destinatariosByRoles($roles));
    }

    private function ensureAusentismoTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'ausentismo.resumen_diario'],
            [
                'name' => 'Resumen diario de ausentismo',
                'description' => 'Notificacion diaria con funcionarios no disponibles.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'high',
                'copy_contexts' => null,
            ]
        );

        OutlookEmailTemplates::ensureActive($type);
    }

    private function buildAusentesHtml(array $ausentes): string
    {
        if ($ausentes === []) {
            return '';
        }

        $rows = '';

        foreach ($ausentes as $ausente) {
            $nombre = (string) ($ausente['funcionario_nombre'] ?? '');
            $cargo = (string) ($ausente['cargo'] ?? '');
            $tipoPermiso = $this->resolveTipoPermisoDisplay($ausente);

            $rows .= '<tr>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($nombre).'</td>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($cargo).'</td>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($tipoPermiso).'</td>'
                .'</tr>';
        }

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;">'
            .'<tr>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Funcionario</th>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Cargo</th>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Tipo permiso</th>'
            .'</tr>'
            .$rows
            .'</table>';
    }

    private function buildAusentesText(array $ausentes): string
    {
        if ($ausentes === []) {
            return '';
        }

        $lines = array_map(function (array $ausente): string {
            $nombre = (string) ($ausente['funcionario_nombre'] ?? '');
            $cargo = (string) ($ausente['cargo'] ?? '');
            $tipoPermiso = $this->resolveTipoPermisoDisplay($ausente);

            return "- {$nombre} | {$cargo} | {$tipoPermiso}";
        }, $ausentes);

        return implode(' ', $lines);
    }

    private function resolveTipoPermisoDisplay(array $ausente): string
    {
        $tipoPermiso = trim((string) ($ausente['tipo_permiso'] ?? ''));

        if (! strcasecmp($tipoPermiso, 'Otro')) {
            $observacion = trim((string) ($ausente['observacion'] ?? ''));

            return $observacion !== '' ? $observacion : 'Otro';
        }

        return $tipoPermiso;
    }
}
