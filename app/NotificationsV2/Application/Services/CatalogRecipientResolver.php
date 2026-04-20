<?php

namespace App\NotificationsV2\Application\Services;

use App\NotificationsV2\Application\Contracts\RecipientResolver;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\Services\FuncionarioCatalog;

class CatalogRecipientResolver implements RecipientResolver
{
    public function __construct(private readonly FuncionarioCatalog $catalog) {}

    public function resolve(NotificationType $notificationType, array $payload, array $options = []): array
    {
        $resolved = [];
        $contextRecipientTypeOption = $options['context_recipient_type'] ?? 'to';
        $contextRecipientType = in_array($contextRecipientTypeOption, ['to', 'cc', 'bcc'], true)
            ? $contextRecipientTypeOption
            : 'to';

        $manualRecipients = $options['recipients'] ?? [];
        if (is_array($manualRecipients) && $manualRecipients !== []) {
            foreach ($manualRecipients as $recipient) {
                $resolved[] = [
                    'type' => $recipient['type'] ?? 'to',
                    'name' => $recipient['name'] ?? null,
                    'email' => $recipient['email'] ?? null,
                    'context' => $recipient['context'] ?? null,
                ];
            }
        }

        $contexts = $options['contexts'] ?? [];
        if (is_array($contexts)) {
            foreach ($contexts as $context) {
                if (! is_string($context) || $context === '') {
                    continue;
                }

                foreach ($this->catalog->destinatariosByContext($context) as $recipient) {
                    $resolved[] = [
                        'type' => $contextRecipientType,
                        'name' => $recipient['nombre'] ?? null,
                        'email' => $recipient['email'] ?? null,
                        'context' => $context,
                    ];
                }
            }
        }

        if (($options['use_all_catalog'] ?? false) === true) {
            foreach ($this->catalog->destinatariosByRoles($this->catalog->roles()) as $recipient) {
                $resolved[] = [
                    'type' => 'to',
                    'name' => $recipient['nombre'] ?? null,
                    'email' => $recipient['email'] ?? null,
                    'context' => 'all_catalog',
                ];
            }
        }

        if ($resolved === [] && isset($payload['recipient_email'])) {
            $resolved[] = [
                'type' => 'to',
                'name' => $payload['recipient_name'] ?? null,
                'email' => $payload['recipient_email'],
                'context' => 'payload',
            ];
        }

        $unique = [];
        foreach ($resolved as $recipient) {
            $email = strtolower(trim((string) ($recipient['email'] ?? '')));
            if ($email === '') {
                continue;
            }

            $bucket = ($recipient['type'] ?? 'to').'|'.$email;
            if (isset($unique[$bucket])) {
                continue;
            }

            $unique[$bucket] = [
                'type' => in_array($recipient['type'] ?? 'to', ['to', 'cc', 'bcc'], true) ? $recipient['type'] : 'to',
                'name' => $recipient['name'] ?? null,
                'email' => $email,
                'context' => $recipient['context'] ?? null,
            ];
        }

        return array_values($unique);
    }
}
