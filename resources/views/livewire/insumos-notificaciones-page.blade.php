<x-slot:header>
    <x-page-heading eyebrow="Insumos · Notificaciones" title="Notificaciones" />
</x-slot:header>

<section class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />
    <div class="flex justify-end">
        <button type="button" wire:click="marcarTodasLeidas" class="rounded-md border border-cyan-700 px-4 py-2 text-sm font-semibold text-cyan-700 hover:bg-cyan-50">Marcar todas como leidas</button>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <article class="rounded-xl border p-5 shadow-sm {{ $notification->read_at ? 'border-slate-200 bg-white' : 'border-cyan-300 bg-cyan-50' }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold text-slate-900">{{ $notification->data['title'] }}</h2>
                        <p class="mt-1 text-sm text-slate-600">{{ $notification->data['message'] }}</p>
                        <p class="mt-2 text-xs text-slate-500">{{ $notification->created_at->format('d-m-Y H:i') }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <button type="button" wire:click="marcarLeida('{{ $notification->id }}')" class="text-sm font-semibold text-cyan-700">Marcar leida</button>
                    @endif
                </div>
                <a href="{{ $notification->data['url'] }}" wire:navigate class="mt-3 inline-block text-sm font-semibold text-cyan-700">Abrir modulo</a>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-sm text-slate-500">No hay notificaciones de Insumos.</div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</section>
