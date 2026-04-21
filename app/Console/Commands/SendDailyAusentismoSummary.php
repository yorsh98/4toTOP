<?php

namespace App\Console\Commands;

use App\NotificationsV2\Application\Services\DailyAusentismoSummaryNotifier;
use Illuminate\Console\Command;

class SendDailyAusentismoSummary extends Command
{
    protected $signature = 'notificaciones:ausentismo-diario {--date= : Fecha de reporte en formato Y-m-d} {--force : Fuerza reenvio ignorando idempotencia}';

    protected $description = 'Genera y encola el resumen diario de ausentismo para notificaciones V2';

    public function handle(DailyAusentismoSummaryNotifier $notifier): int
    {
        $date = $this->option('date');
        $force = (bool) $this->option('force');

        $notifier->notifyForDate(is_string($date) && trim($date) !== '' ? $date : null, $force);

        $this->info('Resumen diario de ausentismo procesado en notificaciones V2.');

        return self::SUCCESS;
    }
}
