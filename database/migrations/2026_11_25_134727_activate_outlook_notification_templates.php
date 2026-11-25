<?php

use App\NotificationsV2\Support\OutlookEmailTemplates;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODES = [
        'ausentismo.resumen_diario',
        'visita_carcel.revision_pp',
        'ttypp.listado.diario',
        'bitacora_pruebas.notificacion',
        'legacy.user_account_status',
        'insumo.solicitado',
        'insumo.asignado',
        'insumo.stock_bajo',
        'turno1.resumen_semanal',
        'turno2.acd.resumen_semanal',
    ];

    public function up(): void
    {
        $this->forExistingTypes(function ($type): void {
            $templates = DB::table('notification_templates')->where('notification_type_id', $type->id);
            $active = (clone $templates)->where('is_active', true);
            $current = (clone $active)->orderByDesc('version')->first();

            // Code deployed before migrating may already have created the Outlook version.
            if ($current && OutlookEmailTemplates::isOutlook((string) $current->body_html)) {
                return;
            }

            // Never record an Outlook row as the "previous" version.
            $previous = $current ?? (clone $templates)->orderByDesc('version')->get()
                ->first(fn ($template) => ! OutlookEmailTemplates::isOutlook((string) $template->body_html));

            $template = OutlookEmailTemplates::forType($type->code);
            $now = now();
            $version = $templates->max('version') + 1;
            $active->update(['is_active' => false, 'updated_at' => $now]);

            DB::table('notification_templates')->insert([
                'notification_type_id' => $type->id,
                'version' => $version,
                'subject' => $previous?->subject ?? $template['subject'],
                'body_html' => $template['body_html'],
                'outlook_rollout_previous_template_id' => $previous?->id ?? 0,
                'body_text' => $template['body_text'],
                'variables_schema' => json_encode($template['variables_schema'], JSON_THROW_ON_ERROR),
                'is_active' => true,
                'activated_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Antes de revertir, vaciar la cola (`php artisan queue:work --stop-when-empty`):
     * los envíos encolados se procesan con la plantilla activa al momento de procesarse.
     */
    public function down(): void
    {
        $this->forExistingTypes(function ($type): void {
            $templates = DB::table('notification_templates')->where('notification_type_id', $type->id);
            $all = (clone $templates)->orderByDesc('version')->get();

            $isOutlook = fn ($template): bool => OutlookEmailTemplates::isOutlook((string) $template->body_html)
                || $this->previousTemplateId($template) !== null;

            [$outlook, $legacy] = [$all->filter($isOutlook), $all->reject($isOutlook)];

            if ($outlook->isEmpty()) {
                return;
            }

            $recordedId = $outlook->map(fn ($template) => $this->previousTemplateId($template))
                ->first(fn ($id) => $id !== null && $id > 0);

            (clone $templates)->whereIn('id', $outlook->pluck('id'))->delete();

            if ($legacy->isEmpty() || $legacy->contains('is_active', true)) {
                return;
            }

            $target = $legacy->firstWhere('id', $recordedId) ?? $legacy->first();
            (clone $templates)->where('id', $target->id)->update(['is_active' => true, 'updated_at' => now()]);
        });
    }

    private function previousTemplateId(?object $template): ?int
    {
        if (isset($template->outlook_rollout_previous_template_id)) {
            return (int) $template->outlook_rollout_previous_template_id;
        }

        return preg_match('/<!-- outlook-rollout-prior-template:(\d+) -->\s*\z/', $template->body_html ?? '', $matches)
            ? (int) $matches[1]
            : null;
    }

    private function forExistingTypes(Closure $callback): void
    {
        DB::transaction(function () use ($callback): void {
            $types = DB::table('notification_types')
                ->whereIn('code', self::CODES)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'code']);

            $types->each($callback);
        });
    }
};
