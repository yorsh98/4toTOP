<?php

return [
    'enabled' => env('NOTIFICATIONS_V2_ENABLED', false),

    'mailer' => env('NOTIFICATIONS_V2_MAILER', env('MAIL_MAILER', 'smtp')),

    'queue' => [
        'connection' => env('NOTIFICATIONS_V2_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),
        'name' => env('NOTIFICATIONS_V2_QUEUE_NAME', 'default'),
    ],

    'schedule' => [
        'birthday_time' => env('NOTIFICATIONS_V2_BIRTHDAY_TIME', '08:00'),
        'daily_ausentismo_time' => env('NOTIFICATIONS_V2_SCHEDULE_TIME', '08:03'),
        'weekly_turno1_day' => (int) env('NOTIFICATIONS_V2_TURNO1_DAY', 5),
        'weekly_turno1_time' => env('NOTIFICATIONS_V2_TURNO1_TIME', '15:00'),
        'timezone' => env('NOTIFICATIONS_V2_SCHEDULE_TIMEZONE', 'America/Santiago'),
    ],

    'ausentismo' => [
        'send_when_empty' => env('NOTIFICATIONS_V2_AUSENTISMO_SEND_WHEN_EMPTY', true),
        'empty_message' => 'no hay ausencias registradas',
    ],

    'turno1_summary' => [
        'tribunal_oral' => env('NOTIFICATIONS_V2_TURNO1_TRIBUNAL', '4to TOP'),
        'recipient_contexts' => ['turnos.notificacion'],
        'recipients' => [],
        'use_all_catalog' => env('NOTIFICATIONS_V2_TURNO1_USE_ALL_CATALOG', false),
    ],
];
