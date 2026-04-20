<?php

namespace App\NotificationsV2\Application\Contracts;

use App\NotificationsV2\Domain\Models\NotificationDispatch;

interface ChannelSender
{
    public function send(NotificationDispatch $dispatch, array $renderedContent): void;
}
