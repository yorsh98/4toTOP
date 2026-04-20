<?php

namespace App\NotificationsV2\Application\Contracts;

use App\NotificationsV2\Domain\Models\NotificationDispatch;

interface NotificationOrchestrator
{
    public function dispatch(string $typeCode, array $payload, array $options = []): NotificationDispatch;
}
