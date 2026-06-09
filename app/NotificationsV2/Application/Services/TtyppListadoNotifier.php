<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\TtyppRegistro;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\DefaultEmailTemplates;
use App\Services\FuncionarioCatalog;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class TtyppListadoNotifier
{
    public function __construct(
        private readonly NotificationOrchestrator $orchestrator,
        private readonly FuncionarioCatalog $funcionarioCatalog,
    ) {}

    public function notifyForDate(Carbon|string $date, string $senderName): ?NotificationDispatch
    {
        $fecha = $this->parseDate($date)->toDateString();
        $registros = $this->recordsForDate($fecha);
        $recipients = $this->recipients();

        if ($recipients === []) {
            return null;
        }

        $payload = $this->payload($fecha, $senderName, $registros);

        if (! config('notifications_v2.enabled', false)) {
            $this->sendLegacyMail($recipients, $payload, $registros);

            return null;
        }

        $this->ensureTypeAndTemplate();

        return $this->orchestrator->dispatch('ttypp.listado.diario', $payload, [
            'event_key' => 'ttypp.listado.diario.'.$fecha,
            'idempotency_key' => 'ttypp.listado.diario.'.$fecha.'.'.Str::uuid(),
            'recipients' => $recipients,
        ]);
    }

    public function recordsForDate(Carbon|string $date): Collection
    {
        $selectedDate = $this->parseDate($date);
        $start = $selectedDate->copy()->startOfDay()->setTimezone('UTC');
        $end = $selectedDate->copy()->endOfDay()->setTimezone('UTC');

        return TtyppRegistro::query()
            ->where(function ($query) use ($selectedDate, $start, $end): void {
                $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                    ->orWhere(function ($legacy) use ($start, $end): void {
                        $legacy->whereNull('fecha_asignacion')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->latest('id')
            ->get();
    }

    public function recipients(): array
    {
        return collect($this->funcionarioCatalog->destinatariosByRoles(['ttypp']))
            ->map(fn (array $recipient): array => [
                'type' => 'to',
                'name' => $recipient['nombre'] ?? null,
                'email' => $recipient['email'] ?? null,
                'context' => 'ttypp.catalogo',
            ])
            ->filter(fn (array $recipient): bool => is_string($recipient['email'] ?? null) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL))
            ->unique('email')
            ->values()
            ->all();
    }

    public function payload(string $fecha, string $senderName, Collection $registros): array
    {
        return [
            'subject' => 'Listado TTyPP - '.$fecha,
            'fecha' => $fecha,
            'sender_name' => $senderName,
            'total_registros' => $registros->count(),
            'tabla_rows_html' => $this->rowsHtml($registros),
            'tabla_rows_text' => $this->rowsText($registros),
            'mensaje_sin_registros' => $registros->isEmpty() ? 'Sin registros TTyPP para esta fecha.' : '',
        ];
    }

    private function parseDate(Carbon|string $date): Carbon
    {
        return $date instanceof Carbon
            ? $date->copy()->timezone('America/Santiago')->startOfDay()
            : Carbon::parse($date, 'America/Santiago')->startOfDay();
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'ttypp.listado.diario'],
            [
                'name' => 'Listado diario TTyPP',
                'description' => 'Notificacion manual del listado TTyPP por fecha.',
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
            $activeTemplate->update([
                'subject' => '{{ subject }}',
                'body_html' => DefaultEmailTemplates::ttyppListadoHtml(),
                'body_text' => DefaultEmailTemplates::ttyppListadoText(),
                'variables_schema' => [
                    'subject',
                    'fecha',
                    'sender_name',
                    'total_registros',
                    'tabla_rows_html',
                    'tabla_rows_text',
                    'mensaje_sin_registros',
                ],
            ]);

            return;
        }

        NotificationTemplate::query()->create([
            'notification_type_id' => $type->id,
            'version' => 1,
            'subject' => '{{ subject }}',
            'body_html' => DefaultEmailTemplates::ttyppListadoHtml(),
            'body_text' => DefaultEmailTemplates::ttyppListadoText(),
            'variables_schema' => [
                'subject',
                'fecha',
                'sender_name',
                'total_registros',
                'tabla_rows_html',
                'tabla_rows_text',
                'mensaje_sin_registros',
            ],
            'is_active' => true,
            'activated_at' => now(),
        ]);
    }

    private function rowsHtml(Collection $registros): string
    {
        if ($registros->isEmpty()) {
            return '<tr><td colspan="12" style="border:1px solid #cbd5e1;padding:10px;text-align:center;color:#64748b;">Sin registros TTyPP para esta fecha.</td></tr>';
        }

        return $registros->map(function (TtyppRegistro $registro): string {
            $cells = [
                $registro->rit,
                $registro->ruc,
                $registro->tipo_audiencia,
                $registro->numero_dias,
                $registro->cantidad,
                $registro->modalidad,
                $registro->encargada_ti_mp,
                $registro->encargada_ti_d,
                $registro->encargada_zoom,
                $registro->acta,
                $registro->sala,
                $registro->jueces,
            ];

            return '<tr>'.collect($cells)
                ->map(fn ($value): string => '<td style="border:1px solid #cbd5e1;padding:6px;">'.e((string) ($value ?: '-')).'</td>')
                ->implode('').'</tr>';
        })->implode('');
    }

    private function rowsText(Collection $registros): string
    {
        if ($registros->isEmpty()) {
            return '';
        }

        return $registros->map(fn (TtyppRegistro $registro): string => implode(' | ', array_filter([
            'RIT '.$registro->rit,
            'RUC '.$registro->ruc,
            'Sala '.$registro->sala,
            'Acta '.$registro->acta,
        ])))->implode(' ; ');
    }

    private function sendLegacyMail(array $recipients, array $payload, Collection $registros): void
    {
        $html = view('emails.v2.ttypp-listado-outlook', [
            'fecha' => $payload['fecha'],
            'senderName' => $payload['sender_name'],
            'registros' => $registros,
        ])->render();

        $from = config('mail.notifications_from', []);

        foreach ($recipients as $recipient) {
            Mail::send([], [], function (Message $message) use ($recipient, $from, $payload, $html): void {
                $message->to($recipient['email'], $recipient['name'] ?: null);
                $message->subject((string) $payload['subject']);

                if (! empty($from['address'])) {
                    $message->from((string) $from['address'], (string) ($from['name'] ?? 'Notificaciones'));
                }

                $message->html($html);
            });
        }
    }
}
