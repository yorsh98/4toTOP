<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\Ausentismo;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\DefaultEmailTemplates;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class DailyAusentismoSummaryNotifier
{
    public function __construct(private readonly NotificationOrchestrator $orchestrator) {}

    public function notifyForDate(CarbonInterface|string|null $date = null): void
    {
        $this->ensureAusentismoTypeAndTemplate();

        $targetDate = $date instanceof CarbonInterface ? Carbon::instance($date) : Carbon::parse($date ?? now());
        $fechaReporte = $targetDate->toDateString();

        $ausentes = Ausentismo::query()
            ->whereDate('fecha_inicio', '<=', $fechaReporte)
            ->whereDate('fecha_termino', '>=', $fechaReporte)
            ->orderBy('funcionario_nombre')
            ->get()
            ->map(fn (Ausentismo $ausentismo): array => [
                'funcionario_nombre' => $ausentismo->funcionario_nombre,
                'cargo' => $ausentismo->cargo,
                'tipo_permiso' => $ausentismo->tipo_permiso,
                'fecha_inicio' => optional($ausentismo->fecha_inicio)->format('Y-m-d'),
                'fecha_termino' => optional($ausentismo->fecha_termino)->format('Y-m-d'),
                'observacion' => $ausentismo->observacion,
            ])
            ->values()
            ->all();

        $totalAusentes = count($ausentes);
        $sendWhenEmpty = (bool) config('notifications_v2.ausentismo.send_when_empty', true);

        if ($totalAusentes === 0 && ! $sendWhenEmpty) {
            return;
        }

        $payload = [
            'fecha_reporte' => $fechaReporte,
            'total_ausentes' => $totalAusentes,
            'ausentes' => $ausentes,
            'ausentes_html' => $this->buildAusentesHtml($ausentes),
            'ausentes_text' => $this->buildAusentesText($ausentes),
            'mensaje_sin_novedades' => $totalAusentes === 0
                ? (string) config('notifications_v2.ausentismo.empty_message', 'no hay ausencias registradas')
                : '',
        ];

        $this->orchestrator->dispatch('ausentismo.resumen_diario', $payload, [
            'event_key' => 'ausentismo.resumen_diario.'.$fechaReporte,
            'use_all_catalog' => true,
        ]);
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
            'subject' => 'Resumen diario de ausentismo - {{ fecha_reporte }}',
            'body_html' => DefaultEmailTemplates::ausentismoResumenHtml(),
            'body_text' => DefaultEmailTemplates::ausentismoResumenText(),
            'variables_schema' => [
                'fecha_reporte',
                'total_ausentes',
                'ausentes',
                'ausentes_html',
                'ausentes_text',
                'mensaje_sin_novedades',
            ],
            'is_active' => true,
            'activated_at' => now(),
        ]);
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
            $tipoPermiso = (string) ($ausente['tipo_permiso'] ?? '');
            $rango = trim(((string) ($ausente['fecha_inicio'] ?? '')).' a '.((string) ($ausente['fecha_termino'] ?? '')));
            $observacion = (string) ($ausente['observacion'] ?? '');

            $rows .= '<tr>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($nombre).'</td>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($cargo).'</td>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($tipoPermiso).'</td>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($rango).'</td>'
                .'<td style="border:1px solid #e5e7eb;padding:8px;">'.e($observacion).'</td>'
                .'</tr>';
        }

        return '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border-collapse:collapse;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;">'
            .'<tr>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Funcionario</th>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Cargo</th>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Tipo permiso</th>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Rango</th>'
            .'<th style="background:#f8fafc;border:1px solid #e5e7eb;padding:8px;text-align:left;">Observacion</th>'
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
            $tipoPermiso = (string) ($ausente['tipo_permiso'] ?? '');
            $fechaInicio = (string) ($ausente['fecha_inicio'] ?? '');
            $fechaTermino = (string) ($ausente['fecha_termino'] ?? '');
            $observacion = (string) ($ausente['observacion'] ?? '');

            return "- {$nombre} | {$cargo} | {$tipoPermiso} | {$fechaInicio} a {$fechaTermino} | {$observacion}";
        }, $ausentes);

        return implode(' ', $lines);
    }
}
