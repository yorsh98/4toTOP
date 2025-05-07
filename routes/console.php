<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Schedule as ScheduleFacade;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Programar el comando diariamente a las 16:00 (4 PM)
Artisan::command('correo:leer-ultimos-schedule', function () {
    Artisan::call('correo:leer-ultimos');
})->purpose('Leer correo de audiencias diariamente')->everyMinute();