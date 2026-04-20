<?php

namespace App\Console\Commands;

use App\NotificationsV2\Application\Services\TurnoOneWeeklySummaryNotifier;
use Illuminate\Console\Command;

class SendWeeklyTurnoOneSummary extends Command
{
    protected $signature = 'notificaciones:turno1-semanal {--date= : Fecha base en formato Y-m-d para calcular semana}';

    protected $description = 'Genera y encola el resumen semanal de Turno 1 para notificaciones V2';

    public function handle(TurnoOneWeeklySummaryNotifier $notifier): int
    {
        $date = $this->option('date');

        $notifier->notifyForWeek(is_string($date) && trim($date) !== '' ? $date : null);

        $this->info('Resumen semanal de Turno 1 procesado en notificaciones V2.');

        return self::SUCCESS;
    }
}
