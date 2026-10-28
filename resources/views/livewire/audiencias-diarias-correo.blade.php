<div x-data="{
    open: false,
    closePanel() {
        if (!this.open) return;

        this.open = false;

        if (this.$wire.envioDispatchId && !['sent', 'failed'].includes(this.$wire.envioStatus)) {
            return;
        }

        window.setTimeout(() => $wire.close(), window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 0 : 160);
    }
}" x-init="$nextTick(() => open = true)" x-on:audiencias-correo-abrir.window="open = true" @if($envioDispatchId && !in_array($envioStatus, ['sent', 'failed'], true)) wire:poll.3s="actualizarEstadoEnvio" @endif>
    <div x-cloak x-show="open" x-transition:enter="transition-opacity duration-150 ease-out motion-reduce:transition-none"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity duration-150 ease-in motion-reduce:transition-none"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-40">
        <button type="button" class="absolute inset-0 w-full h-full bo-scrim" x-on:click="closePanel()" aria-label="Cerrar"></button>
    </div>

    <div x-cloak x-show="open" aria-modal="true" role="dialog"
        x-transition:enter="transition-transform duration-200 ease-out motion-reduce:transition-none"
        x-transition:enter-start="translate-x-full motion-reduce:translate-x-0"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition-transform duration-150 ease-in motion-reduce:transition-none"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="translate-x-full motion-reduce:translate-x-0"
        class="fixed inset-y-0 right-0 z-40 flex flex-col w-full max-w-xl bo-panel shadow-xl">
            <div class="flex items-center justify-between px-4 py-3 border-b">
                <h3 class="text-base font-semibold">Difusión por correo - {{ \Carbon\Carbon::parse($fecha)->format('d-m-Y') }}</h3>
                <button type="button" class="bo-btn bo-btn--secondary p-2 rounded " x-on:click="closePanel()" aria-label="Cerrar">×</button>
            </div>

            <div class="flex-1 p-4 space-y-6 overflow-y-auto">
                <section class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="font-medium">Destinatarios de este envío</h4>
                        <button type="button" wire:click="openListModal" class="bo-btn bo-btn--secondary px-3 py-1.5 text-sm border rounded-md ">Administrar lista...</button>
                    </div>

                    <div class="flex gap-2">
                        <input type="email" wire:model="newEmail" wire:keydown.enter.prevent="addRecipient"
                            placeholder="correo@dominio.cl" class="bo-input flex-1 px-3 py-2 border bo-border rounded-md">
                        <button type="button" wire:click="addRecipient" class="bo-btn bo-btn--primary px-3 py-2   rounded-md ">Agregar</button>
                    </div>
                    @error('newEmail') <p class="text-sm bo-text--danger">{{ $message }}</p> @enderror

                    <details class="text-sm border rounded-md">
                        <summary class="flex items-center justify-between px-3 py-2 cursor-pointer bo-panel-subtle bo-strong">
                            <span>{{ count($recipients) }} destinatarios</span>
                            <span class="text-xs font-medium bo-text--info">Ver lista</span>
                        </summary>
                        <ul class="max-h-48 overflow-y-auto divide-y">
                            @forelse($recipients as $index => $recipient)
                                <li class="flex items-center justify-between px-3 py-2">
                                    <span class="truncate">{{ $recipient }}</span>
                                    <button type="button" wire:click="removeRecipient({{ $index }})" class="bo-btn bo-btn--secondary bo-text--danger ">Quitar</button>
                                </li>
                            @empty
                                <li class="px-3 py-2 bo-muted">Sin destinatarios.</li>
                            @endforelse
                        </ul>
                    </details>
                    @error('recipients') <p class="text-sm bo-text--danger">{{ $message }}</p> @enderror
                </section>

                <section class="space-y-3">
                    <h4 class="font-medium">Pie de firma</h4>
                    <select wire:model.live="firmaId" class="bo-select w-full px-3 py-2 border bo-border rounded-md">
                        @foreach($signatures as $signature)
                            <option value="{{ $signature['id'] }}">{{ $signature['nombre'] }}</option>
                        @endforeach
                    </select>
                    <div class="p-3 border rounded-md bo-panel-subtle">{!! $firmaPreview !!}</div>
                </section>
            </div>

            <div class="flex items-center gap-2 px-4 py-3 border-t">
                @if($envioDispatchId)
                    @php($progress = $envioTotal ? (($envioEnviados + $envioFallidos) / $envioTotal) * 100 : 0)
                    <div class="mr-auto min-w-56 text-sm bo-muted">
                        <div class="flex justify-between mb-1"><span>Envío #{{ $envioDispatchId }}</span><span>{{ (int) round($progress) }}%</span></div>
                        <div class="h-2 overflow-hidden rounded bo-panel-subtle"><div class="h-full bo-action-primary" style="width: {{ $progress }}%"></div></div>
                        <div class="mt-1">{{ $envioEnviados }} enviados, {{ $envioFallidos }} no enviados</div>
                    </div>
                @endif
                <button type="button" x-on:click="closePanel()" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md ">Cancelar</button>
                <button type="button" wire:click="enviarCorreoDifusion" wire:loading.attr="disabled" wire:target="enviarCorreoDifusion"
                    @disabled($envioDispatchId && !in_array($envioStatus, ['sent', 'failed'], true))
                    class="bo-btn bo-btn--primary px-4 py-2   rounded-md disabled:opacity-50">
                    <span wire:loading.remove wire:target="enviarCorreoDifusion">Enviar difusión</span>
                    <span wire:loading wire:target="enviarCorreoDifusion">Enviando...</span>
                </button>
            </div>
    </div>

    @if($listModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center" aria-modal="true" role="dialog">
            <button type="button" class="absolute inset-0 w-full h-full bo-scrim" wire:click="$set('listModal', false)" aria-label="Cerrar"></button>
            <div class="relative w-full max-w-2xl bo-panel border rounded-lg shadow-xl">
                <div class="flex items-center justify-between px-4 py-3 border-b">
                    <h3 class="text-base font-semibold">Lista de difusión</h3>
                    <button type="button" wire:click="$set('listModal', false)" aria-label="Cerrar">×</button>
                </div>
                <div class="p-4 space-y-4">
                    <div class="flex gap-2">
                        <input type="email" wire:model="newEmailDb" wire:keydown.enter.prevent="addRecipientToDb" placeholder="correo@dominio.cl" class="bo-input flex-1 px-3 py-2 border bo-border rounded-md">
                        <button type="button" wire:click="addRecipientToDb" class="bo-btn bo-btn--primary px-3 py-2   rounded-md">Agregar</button>
                    </div>
                    @error('newEmailDb') <p class="text-sm bo-text--danger">{{ $message }}</p> @enderror
                    <ul class="max-h-[48vh] overflow-y-auto divide-y border rounded-md">
                        @forelse($recipientsDb as $recipient)
                            <li class="flex items-center justify-between px-3 py-2 text-sm">
                                <span>{{ $recipient['email'] }}</span>
                                <button type="button" wire:click="removeRecipientFromDb({{ $recipient['id'] }})" class="bo-btn bo-btn--secondary bo-text--danger">Eliminar</button>
                            </li>
                        @empty
                            <li class="px-3 py-2 bo-muted">No hay correos en la lista.</li>
                        @endforelse
                    </ul>
                </div>
                <div class="flex justify-end gap-2 px-4 py-3 border-t">
                    <button type="button" wire:click="$set('listModal', false)" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md">Cerrar</button>
                    <button type="button" wire:click="refreshRecipientsFromDb" class="bo-btn bo-btn--secondary px-3 py-2 border rounded-md">Refrescar</button>
                    <button type="button" wire:click="loadDbIntoSendList" class="bo-btn bo-btn--primary px-3 py-2   rounded-md">Usar esta lista</button>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        document.addEventListener('correo-difusion-finalizada', (event) => {
            const { status, dispatchId, sent, failed, failures = [] } = event.detail;
            const completedWithoutFailures = status === 'sent' && failed === 0;

            const alert = Swal?.fire?.({
                icon: completedWithoutFailures ? 'success' : 'warning',
                title: completedWithoutFailures ? 'Programación enviada' : 'Envío completado con advertencias',
                text: completedWithoutFailures
                    ? `${sent} enviados.`
                    : `${sent} enviados, ${failed} no enviados. Despacho #${dispatchId}.`,
                showDenyButton: failed > 0,
                denyButtonText: 'Ver destinatarios no enviados',
                confirmButtonText: 'Cerrar',
            });

            if (!alert) {
                $wire.close();

                return;
            }

            alert.then((result) => {
                if (!result.isDenied) return;

                const escape = (value) => String(value).replace(/[&<>'"]/g, (character) => ({
                    '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#039;', '"': '&quot;'
                })[character]);
                const details = failures.map(({ email, reason }) => `<li><strong>${escape(email)}</strong>: ${escape(reason || 'Rechazado por el servidor SMTP.')}</li>`).join('');

                Swal?.fire?.({
                    icon: 'info',
                    title: 'Destinatarios no enviados',
                    html: `<ul class="text-left">${details}</ul><p class="mt-3 text-sm bo-muted">Despacho #${dispatchId}</p>`,
                });
            }).finally(() => $wire.close());
        });
    </script>
    @endscript
</div>
