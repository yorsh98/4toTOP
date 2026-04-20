<?php

namespace App\NotificationsV2\Application\Contracts;

use App\NotificationsV2\Domain\Models\NotificationTemplate;

interface TemplateRenderer
{
    public function render(NotificationTemplate $template, array $payload): array;
}
