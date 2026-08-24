<?php

namespace App\Console\Commands;

use App\NotificationsV2\Application\Services\VisitaCarcelRevisionPpNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendVisitaCarcelRevisionPpSummary extends Command
{
    protected $signature = 'notificaciones:visita-carcel-revision-pp {fecha? : La fecha del resumen Y-m-d}';

    protected $description = 'Envía el resumen de imputados entre 160 y 180 días desde su última revisión de PP.';

    public function handle(VisitaCarcelRevisionPpNotifier $notifier): int
    {
        $date = $this->argument('fecha') ? Carbon::parse($this->argument('fecha'), 'America/Santiago') : now('America/Santiago');
        $notifier->notifyForDate($date);
        $this->info('Resumen de revisión de PP procesado.');

        return self::SUCCESS;
    }
}
