<?php

namespace App\Console\Commands;

use App\NotificationsV2\Application\Services\TurnoTwoAcdWeeklySummaryNotifier;
use Illuminate\Console\Command;

class SendWeeklyTurnoTwoAcdSummary extends Command
{
    protected $signature = 'notificaciones:turno2-acd-periodo {--date= : Fecha base en formato Y-m-d para calcular periodo}';

    protected $description = 'Genera y encola el resumen por periodo de Turno 2 ACD para notificaciones V2';

    public function handle(TurnoTwoAcdWeeklySummaryNotifier $notifier): int
    {
        $date = $this->option('date');

        $notifier->notifyForWeek(is_string($date) && trim($date) !== '' ? $date : null);

        $this->info('Resumen de Turno 2 ACD por periodo procesado en notificaciones V2.');

        return self::SUCCESS;
    }
}
