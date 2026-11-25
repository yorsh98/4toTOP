<?php

namespace App\NotificationsV2\Application\Services;

use App\Notifications\BitacoraPruebasNotification;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class BitacoraPruebasNotifier
{
    public function __construct(private readonly NotificationOrchestrator $orchestrator) {}

    /**
     * @param  array<int, string>  $destinatarios
     */
    public function notify(array $destinatarios, string $senderName, string $documentosUrl, string $bitacoraPublicUrl, ?string $rit = null): void
    {
        $recipients = collect($destinatarios)
            ->map(fn (string $email): string => strtolower(trim($email)))
            ->filter(fn (string $email): bool => $email !== '' && (bool) filter_var($email, FILTER_VALIDATE_EMAIL))
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        if (! config('notifications_v2.enabled', false)) {
            foreach ($recipients as $email) {
                Notification::route('mail', $email)
                    ->notify(new BitacoraPruebasNotification($senderName, $documentosUrl, $bitacoraPublicUrl, $rit));
            }

            return;
        }

        $this->ensureTypeAndTemplate();

        $payload = $this->buildPayload($senderName, $documentosUrl, $bitacoraPublicUrl, $rit);

        $this->orchestrator->dispatch('bitacora_pruebas.notificacion', $payload, [
            'event_key' => 'bitacora_pruebas.notificacion.'.Str::uuid(),
            'idempotency_key' => 'bitacora_pruebas.manual.'.Str::uuid(),
            'recipients' => $recipients
                ->map(fn (string $email): array => [
                    'type' => 'to',
                    'name' => null,
                    'email' => $email,
                    'context' => 'bitacora_pruebas.notificacion.manual',
                ])
                ->all(),
        ]);
    }

    public function buildPayload(string $senderName, string $documentosUrl, string $bitacoraPublicUrl, ?string $rit = null): array
    {
        return [
            'subject' => 'Notificacion de Bitacora de Pruebas',
            'sender_name' => $senderName,
            'line' => $senderName.' le ha enviado esta notificacion.',
            'line_html' => e($senderName.' le ha enviado esta notificacion.'),
            'rit' => trim((string) $rit),
            'rit_html' => e(trim((string) $rit)),
            'documentos_url' => $documentosUrl,
            'documentos_url_html' => e($documentosUrl),
            'bitacora_public_url' => $bitacoraPublicUrl,
            'bitacora_public_url_html' => e($bitacoraPublicUrl),
        ];
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'bitacora_pruebas.notificacion'],
            [
                'name' => 'Notificacion bitacora de pruebas',
                'description' => 'Notificacion manual de bitacora de pruebas para enlaces de documentos y portal publico.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'normal',
                'copy_contexts' => null,
            ]
        );

        OutlookEmailTemplates::ensureActive($type);
    }
}
