<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

ScheduleFacade::command('notificaciones:ausentismo-diario')
    ->dailyAt((string) config('notifications_v2.schedule.daily_ausentismo_time', '08:03'))
    ->timezone((string) config('notifications_v2.schedule.timezone', 'America/Santiago'))
    ->withoutOverlapping();

// Turno 1 weekly summary cron disabled by request.
// If needed again, schedule the command below as a CRON task:
// ScheduleFacade::command('notificaciones:turno1-semanal')
//     ->weeklyOn((int) config('notifications_v2.schedule.weekly_turno1_day', 5), (string) config('notifications_v2.schedule.weekly_turno1_time', '15:00'))
//     ->timezone((string) config('notifications_v2.schedule.timezone', 'America/Santiago'))
//     ->withoutOverlapping();
