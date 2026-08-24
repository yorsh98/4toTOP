<?php

namespace App\NotificationsV2\Application\Services;

use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\DefaultEmailTemplates;
use App\Services\FuncionarioCatalog;
use App\Services\VisitaCarcelRevisionPpService;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class VisitaCarcelRevisionPpNotifier
{
    public function __construct(
        private readonly NotificationOrchestrator $orchestrator,
        private readonly FuncionarioCatalog $funcionarioCatalog,
        private readonly VisitaCarcelRevisionPpService $revisionPp,
    ) {}

    public function notifyForDate(CarbonInterface $date): void
    {
        $date = $date->copy()->timezone('America/Santiago')->startOfDay();
        $records = $this->revisionPp->recordsForDate($date);
        $this->ensureTypeAndTemplate();

        $this->orchestrator->dispatch('visita_carcel.revision_pp', [
            'fecha' => $date->format('d/m/Y'),
            'total_imputados' => $records->count(),
            'tabla_rows_html' => $this->rowsHtml($records),
            'tabla_rows_text' => $this->rowsText($records),
            'mensaje_sin_imputados' => $records->isEmpty() ? 'No se encuentran imputados para la revision de PP.' : '',
        ], [
            'event_key' => 'visita_carcel.revision_pp.'.$date->toDateString(),
            'idempotency_key' => 'visita_carcel.revision_pp.'.$date->toDateString(),
            'recipients' => collect($this->funcionarioCatalog->destinatariosByRoles(['jefatura']))
                ->map(fn (array $recipient): array => ['type' => 'to', 'name' => $recipient['nombre'] ?? null, 'email' => $recipient['email'] ?? null, 'context' => 'visita_carcel.jefatura'])
                ->all(),
        ]);
    }

    private function ensureTypeAndTemplate(): void
    {
        $type = NotificationType::query()->firstOrCreate(['code' => 'visita_carcel.revision_pp'], [
            'name' => 'Revisión de prisión preventiva',
            'description' => 'Resumen de imputados entre los días 160 y 180 desde su última revisión de PP.',
            'channel' => 'mail',
            'is_active' => true,
            'priority' => 'high',
            'copy_contexts' => null,
        ]);

        NotificationTemplate::query()->updateOrCreate([
            'notification_type_id' => $type->id,
            'is_active' => true,
        ], [
            'version' => 1,
            'subject' => 'Revisión de PP - {{ fecha }}',
            'body_html' => DefaultEmailTemplates::visitaCarcelRevisionPpHtml(),
            'body_text' => DefaultEmailTemplates::visitaCarcelRevisionPpText(),
            'variables_schema' => ['fecha', 'total_imputados', 'tabla_rows_html', 'tabla_rows_text', 'mensaje_sin_imputados'],
            'activated_at' => now(),
        ]);
    }

    private function rowsHtml(Collection $records): string
    {
        return $records->map(function ($imputado): string {
            return '<tr>'.collect([
                $imputado->nombre,
                $imputado->rut,
                $imputado->causa->rit,
                $imputado->causa->ruc,
                $imputado->ultima_rev_pp->format('d/m/Y'),
                $imputado->dias_plazo,
            ])->map(fn ($value): string => '<td style="border:1px solid #cbd5e1;padding:6px;">'.e((string) $value).'</td>')->implode('').'</tr>';
        })->implode('');
    }

    private function rowsText(Collection $records): string
    {
        return $records->map(fn ($imputado): string => implode(' | ', [$imputado->nombre, $imputado->rut, $imputado->causa->rit, $imputado->causa->ruc, $imputado->ultima_rev_pp->format('d/m/Y'), $imputado->dias_plazo]))->implode("\n");
    }
}
