<?php

namespace App\NotificationsV2\Application\Contracts;

use App\NotificationsV2\Domain\Models\NotificationType;

interface RecipientResolver
{
    public function resolve(NotificationType $notificationType, array $payload, array $options = []): array;
}
