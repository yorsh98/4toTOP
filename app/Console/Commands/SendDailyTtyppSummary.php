<?php

namespace App\Console\Commands;

use App\Models\Audiencia;
use App\Models\TtyppRegistro;
use App\NotificationsV2\Application\Services\TtyppListadoNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDailyTtyppSummary extends Command
{
    protected $signature = 'notificaciones:ttypp-diario {fecha? : La fecha del listado Y-m-d}';

    protected $description = 'Enriquece registros TTyPP desde audiencias y envía el resumen diario a los encargados registrados.';

    public function handle(TtyppListadoNotifier $notifier): int
    {
        $fechaInput = $this->argument('fecha');
        $date = $fechaInput ? Carbon::parse($fechaInput) : now('America/Santiago');
        $fecha = $date->toDateString();

        $this->info("Procesando envío de listado TTyPP para la fecha: {$fecha}");

        $registros = $notifier->recordsForDate($fecha);
        if ($registros->isEmpty()) {
            $this->info("No hay registros TTyPP cargados para la fecha {$fecha}. No se envía notificación.");

            return 0;
        }

        // Enriquecer registros con datos de audiencias antes de enviar
        $enriched = $this->enrichFromAudiencias($registros);
        $this->info("Registros enriquecidos desde audiencias: {$enriched}.");

        $recipients = $notifier->recipients();
        if (empty($recipients)) {
            $this->error("No hay destinatarios con rol 'ttypp' configurados en el catálogo de funcionarios.");

            return 1;
        }

        $this->info('Enviando a '.count($recipients).' destinatario(s)...');

        $dispatch = $notifier->notifyForDate($fecha, 'Sistema Automático');

        if ($dispatch) {
            $this->info("Notificación encolada correctamente. Dispatch ID: {$dispatch->id}");
        } else {
            $this->info('Notificación enviada en modo legacy (sin colas V2).');
        }

        return 0;
    }

    /**
     * Enriquece los registros TTyPP con campos de la tabla audiencias
     * (tipo_audiencia, acta, sala, jueces) que se asignan después de la carga inicial.
     */
    private function enrichFromAudiencias($registros): int
    {
        $enriched = 0;

        /** @var TtyppRegistro $registro */
        foreach ($registros as $registro) {
            $rit = trim((string) $registro->rit);
            if ($rit === '') {
                continue;
            }

            $audiencia = Audiencia::query()
                ->where('rit', $rit)
                ->latest('id')
                ->first();

            if (! $audiencia) {
                continue;
            }

            $updates = [];

            if (empty(trim((string) $registro->tipo_audiencia)) && ! empty($audiencia->tipo_audiencia)) {
                $updates['tipo_audiencia'] = (string) $audiencia->tipo_audiencia;
            }

            if (empty(trim((string) $registro->acta)) && ! empty($audiencia->acta)) {
                $updates['acta'] = (string) $audiencia->acta;
            }

            if (empty(trim((string) $registro->sala)) && ! empty($audiencia->sala)) {
                $updates['sala'] = (string) $audiencia->sala;
            }

            if (empty(trim((string) $registro->jueces))) {
                $jueces = collect([$audiencia->JuezP, $audiencia->JuezR, $audiencia->JuezI])
                    ->filter(fn ($j) => ! empty(trim((string) $j)))
                    ->implode(', ');

                if ($jueces !== '') {
                    $updates['jueces'] = $jueces;
                }
            }

            if (! empty($updates)) {
                $updates['updated_by'] = null; // Sistema automático
                $registro->update($updates);
                $enriched++;
            }
        }

        return $enriched;
    }
}
