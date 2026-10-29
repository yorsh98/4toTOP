<x-slot:header>
    <x-page-heading eyebrow="Insumos · Notificaciones" title="Notificaciones" />
</x-slot:header>

<section wire:poll.15s.visible class="mx-auto max-w-7xl space-y-4 px-4 py-2 sm:px-6 lg:px-8">
    <x-insumos-nav />
    <div class="flex justify-end">
        <button type="button" wire:click="marcarTodasLeidas" class="rounded-md border bo-border px-4 py-2 text-sm font-semibold bo-btn bo-btn--secondary">Marcar todas como leidas</button>
    </div>

    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <article class="rounded-xl border p-5 shadow-sm {{ $notification->read_at ? 'bo-panel' : 'bo-panel-subtle' }} bo-border">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="font-semibold bo-strong">{{ $notification->data['title'] }}</h2>
                        <p class="mt-1 text-sm bo-muted">{{ $notification->data['message'] }}</p>
                        <p class="mt-2 text-xs bo-muted">{{ $notification->created_at->format('d-m-Y H:i') }}</p>
                    </div>
                    @if (! $notification->read_at)
                        <button type="button" wire:click="marcarLeida('{{ $notification->id }}')" class="text-sm font-semibold bo-text--info">Marcar leida</button>
                    @endif
                </div>
                <a href="{{ $notification->data['url'] }}" wire:navigate class="mt-3 inline-block text-sm font-semibold bo-text--info">Abrir modulo</a>
            </article>
        @empty
            <div class="rounded-xl border border-dashed bo-border bo-panel-subtle p-6 text-sm bo-muted">No hay notificaciones de Insumos.</div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</section>
