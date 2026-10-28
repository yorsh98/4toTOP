<x-slot:header><x-page-heading eyebrow="Herramientas · Backoffice" title="Registro de conversiones"><x-slot:actions><a href="{{ route('conversions.backoffice') }}" class="text-sm font-semibold bo-text--info hover:underline">Volver al conversor</a></x-slot:actions></x-page-heading></x-slot:header>

<div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-3 rounded-xl border bo-border bo-panel p-5 sm:grid-cols-4 bo-card">
        <label class="text-sm font-medium bo-strong">Área<select wire:model.live="area" class="mt-1 block w-full rounded-lg bo-border text-sm bo-select"><option value="">Todas</option><option value="frontoffice">Frontoffice</option><option value="backoffice">Backoffice</option></select></label>
        <label class="text-sm font-medium bo-strong">Herramienta<select wire:model.live="format" class="mt-1 block w-full rounded-lg bo-border text-sm bo-select"><option value="">Todas</option>@foreach (config('conversions.tools') as $toolFormat => $toolConfig)<option value="{{ $toolFormat }}">{{ $toolConfig['label'] }}</option>@endforeach</select></label>
        <label class="text-sm font-medium bo-strong">Estado<select wire:model.live="status" class="mt-1 block w-full rounded-lg bo-border text-sm bo-select"><option value="">Todos</option><option value="queued">En espera</option><option value="processing">Convirtiendo</option><option value="ready">Listo</option><option value="failed">Fallido</option></select></label>
        <label class="text-sm font-medium bo-strong">Fecha<input type="date" wire:model.live="date" class="mt-1 block w-full rounded-lg bo-border text-sm bo-input"></label>
    </div>
    <div class="overflow-x-auto rounded-xl border bo-border bo-panel shadow-sm bo-card">
        <table class="min-w-full text-left text-sm bo-table">
            <thead class="bo-panel-subtle text-xs uppercase tracking-wider bo-muted"><tr><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Área / persona</th><th class="px-4 py-3">IP</th><th class="px-4 py-3">Archivo → resultado</th><th class="px-4 py-3">Estado</th></tr></thead>
            <tbody>
                @forelse ($jobs as $job)
                    <tr wire:key="history-{{ $job->id }}"><td class="whitespace-nowrap px-4 py-3">{{ $job->created_at->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ ucfirst($job->area) }}<br><span class="font-medium">{{ $job->actor_name }}</span>@if ($job->user_id) <span class="bo-muted">(#{{ $job->user_id }})</span> @endif</td><td class="px-4 py-3 font-mono text-xs">{{ $job->ip_address }}</td><td class="min-w-[18rem] px-4 py-3 [overflow-wrap:anywhere]">{{ $job->original_name }}<br><span class="bo-muted">{{ $job->result_name }}</span></td><td class="px-4 py-3">{{ ['queued' => 'En espera', 'processing' => 'Convirtiendo', 'ready' => 'Listo', 'failed' => 'Fallido'][$job->status] ?? $job->status }}@if ($job->engine)<br><span class="text-xs bo-muted">{{ $job->engine }}</span>@endif @if ($job->notice)<br><span class="text-xs bo-text--warn">{{ $job->notice }}</span>@endif</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center bo-muted">No hay conversiones para estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $jobs->links() }}
</div>
