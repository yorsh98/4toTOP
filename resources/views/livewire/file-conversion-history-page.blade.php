<x-slot:header><x-page-heading eyebrow="Herramientas · Backoffice" title="Registro de conversiones"><x-slot:actions><a href="{{ route('conversions.backoffice') }}" class="text-sm font-semibold text-sky-700 hover:underline">Volver al conversor</a></x-slot:actions></x-page-heading></x-slot:header>

<div class="mx-auto max-w-7xl space-y-5 px-4 py-8 sm:px-6 lg:px-8">
    <div class="grid gap-3 rounded-xl border border-slate-200 bg-white p-5 sm:grid-cols-4">
        <label class="text-sm font-medium text-slate-700">Área<select wire:model.live="area" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Todas</option><option value="frontoffice">Frontoffice</option><option value="backoffice">Backoffice</option></select></label>
        <label class="text-sm font-medium text-slate-700">Formato<select wire:model.live="format" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Todos</option><option value="pdf">PDF → Word</option><option value="m4a">M4A → MP3</option></select></label>
        <label class="text-sm font-medium text-slate-700">Estado<select wire:model.live="status" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"><option value="">Todos</option><option value="queued">En espera</option><option value="processing">Convirtiendo</option><option value="ready">Listo</option><option value="failed">Fallido</option></select></label>
        <label class="text-sm font-medium text-slate-700">Fecha<input type="date" wire:model.live="date" class="mt-1 block w-full rounded-lg border-slate-300 text-sm"></label>
    </div>
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-600"><tr><th class="px-4 py-3">Fecha</th><th class="px-4 py-3">Área / persona</th><th class="px-4 py-3">IP</th><th class="px-4 py-3">Archivo → resultado</th><th class="px-4 py-3">Estado</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($jobs as $job)
                    <tr wire:key="history-{{ $job->id }}"><td class="whitespace-nowrap px-4 py-3">{{ $job->created_at->format('d/m/Y H:i') }}</td><td class="px-4 py-3">{{ ucfirst($job->area) }}<br><span class="font-medium">{{ $job->actor_name }}</span>@if ($job->user_id) <span class="text-slate-500">(#{{ $job->user_id }})</span> @endif</td><td class="px-4 py-3 font-mono text-xs">{{ $job->ip_address }}</td><td class="max-w-xs break-all px-4 py-3">{{ $job->original_name }}<br><span class="text-slate-500">{{ $job->result_name }}</span></td><td class="px-4 py-3">{{ ['queued' => 'En espera', 'processing' => 'Convirtiendo', 'ready' => 'Listo', 'failed' => 'Fallido'][$job->status] ?? $job->status }}</td></tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No hay conversiones para estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $jobs->links() }}
</div>
