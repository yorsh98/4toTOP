<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\User;
use App\Notifications\UserAccountStatusNotification;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\OutlookEmailTemplates;

class LegacyUserAccountStatusNotifier
{
    public function __construct(private readonly NotificationOrchestrator $orchestrator) {}

    public function notify(User $user, string $status, ?string $adminName = null): void
    {
        if (! config('notifications_v2.enabled', false)) {
            $user->notify(new UserAccountStatusNotification($status, $adminName));

            return;
        }

        $this->ensureTypeAndTemplate();

        $payload = $this->buildPayload($user, $status, $adminName);

        $this->orchestrator->dispatch('legacy.user_account_status', $payload, [
            'event_key' => "legacy.user_account_status.{$status}.{$user->id}",
            'recipients' => [[
                'type' => 'to',
                'name' => $user->name,
                'email' => $user->email,
                'context' => 'legacy.user_account_status',
            ]],
        ]);
    }

    public function buildPayload(User $user, string $status, ?string $adminName = null): array
    {
        [$subject, $line] = $this->resolveStatusContent($status);

        return [
            'user_name' => $user->name,
            'user_name_html' => e($user->name),
            'status' => $status,
            'subject' => $subject,
            'line' => $line,
            'admin_name' => $adminName,
            'admin_line' => $adminName ? '<p style="margin:0 0 10px 0;">Gestion realizada por: '.e($adminName).'.</p>' : '',
            'admin_line_text' => $adminName ? 'Gestion realizada por: '.$adminName.'.' : '',
        ];
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(
            ['code' => 'legacy.user_account_status'],
            [
                'name' => 'Legacy - Estado de cuenta de usuario',
                'description' => 'Adaptador legacy para estado de cuenta en modulo Seguridad.',
                'channel' => 'mail',
                'is_active' => true,
                'priority' => 'normal',
                'copy_contexts' => null,
            ]
        );

        OutlookEmailTemplates::ensureActive($type);
    }

    private function resolveStatusContent(string $status): array
    {
        return match ($status) {
            'approved' => [
                'Tu cuenta fue aprobada',
                'Tu cuenta ya fue aprobada y puedes iniciar sesion en el sistema.',
            ],
            'rejected' => [
                'Tu cuenta fue rechazada',
                'Tu solicitud de acceso fue rechazada por un administrador.',
            ],
            'suspended' => [
                'Tu cuenta fue suspendida',
                'Tu cuenta fue suspendida y no podras ingresar hasta que sea reactivada.',
            ],
            'unsuspended' => [
                'Tu cuenta fue reactivada',
                'Tu cuenta fue reactivada y ya puedes volver a ingresar al sistema.',
            ],
            default => [
                'Actualizacion de cuenta',
                'Se actualizo el estado de tu cuenta en el sistema.',
            ],
        };
    }
}
