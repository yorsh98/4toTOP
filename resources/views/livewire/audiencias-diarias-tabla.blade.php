<div>
    <div class="mb-3 text-sm bo-muted">
        Mostrando audiencias del <span class="font-semibold">{{ \Carbon\Carbon::parse($fecha)->format('d-m-Y') }}</span>.
        Total: <span class="font-semibold">{{ count($audiencias) }}</span>
    </div>

    @if (count($audiencias) === 0)
        <div class="p-4 text-sm border rounded-md bo-panel-subtle bo-text--warn">
            No se encontraron audiencias para la fecha seleccionada.
        </div>
    @else
        <div class="overflow-x-auto border rounded-md" wire:loading.class="opacity-50" wire:target="fecha, toggleDetalle, eliminar">
            <table class="bo-table bo-audiencias-table text-sm">
                <thead class="bo-panel-subtle">
                    <tr class="text-left bo-muted">
                        <th class="px-3 py-2 text-right">Acciones</th>
                        <th class="px-3 py-2">Sala</th>
                        <th class="px-3 py-2">RIT</th>
                        <th class="px-3 py-2">RUC</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Hora</th>
                        <th class="px-3 py-2">Encargado causa</th>
                    </tr>
                </thead>
                <tbody class="bo-panel divide-y bo-border">
                    @foreach ($audiencias as $aud)
                        <tr wire:key="audiencia-{{ $aud->id }}">
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button type="button" wire:click="toggleDetalle({{ $aud->id }})"
                                    aria-expanded="{{ $detalleId === $aud->id ? 'true' : 'false' }}"
                                    aria-controls="audiencia-detalle-{{ $aud->id }}"
                                    aria-label="{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}"
                                    title="{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}"
                                    class="bo-btn-icon group relative inline-flex h-8 w-8 items-center justify-center rounded-md bo-text--info transition-all duration-150 ease-out motion-safe:hover:-translate-y-0.5 hover:shadow-sm focus:outline-none focus-visible:ring-2 ">
                                    <i class="fas {{ $detalleId === $aud->id ? 'fa-chevron-up' : 'fa-chevron-down' }}" aria-hidden="true"></i>
                                    <span class="sr-only">{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}</span>
                                    <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded px-2 py-1 text-xs font-medium bo-action-primary opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}</span>
                                </button>
                                @can('audiencias.editar')
                                    <button type="button" wire:click="editar({{ $aud->id }})"
                                        aria-label="Editar audiencia"
                                        title="Editar audiencia"
                                        class="bo-btn-icon group relative inline-flex h-8 w-8 items-center justify-center rounded-md bo-text--warn transition-all duration-150 ease-out motion-safe:hover:-translate-y-0.5  hover:shadow-sm focus:outline-none focus-visible:ring-2 ">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                        <span class="sr-only">Editar audiencia</span>
                                        <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded px-2 py-1 text-xs font-medium bo-action-primary opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">Editar audiencia</span>
                                    </button>
                                @endcan
                                <button type="button" class="bo-btn-icon group relative inline-flex h-8 w-8 items-center justify-center rounded-md bo-text--danger transition-all duration-150 ease-out motion-safe:hover:-translate-y-0.5 hover:shadow-sm focus:outline-none focus-visible:ring-2 "
                                    aria-label="Eliminar audiencia {{ $aud->rit }}"
                                    title="Eliminar audiencia"
                                    x-on:click="confirmDestructiveAction('¿Eliminar audiencia?', 'RIT {{ $aud->rit }}. Esta acción no se puede deshacer.').then(result => { if (result.isConfirmed) $wire.eliminar({{ $aud->id }}) })">
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                    <span class="sr-only">Eliminar audiencia</span>
                                    <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded px-2 py-1 text-xs font-medium bo-action-primary opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">Eliminar audiencia</span>
                                </button>
                            </td>
                            <td class="px-3 py-2">{{ $aud->sala ?: 'vacío' }}</td>
                            <td class="px-3 py-2 font-medium">{{ $aud->rit ?: 'vacío' }}</td>
                            <td class="px-3 py-2">{{ $aud->ruc ?: 'vacío' }}</td>
                            <td class="px-3 py-2">{{ $aud->tipo_audiencia ?: 'vacío' }}</td>
                            <td class="px-3 py-2">{{ $aud->hora_inicio?->format('H:i') ?? 'vacío' }}</td>
                            <td class="px-3 py-2">{{ $aud->encargado_sala ?? $aud->encargado_causa ?: 'vacío' }}</td>
                        </tr>
                        @if ($detalleId === $aud->id)
                            <tr wire:key="audiencia-detalle-{{ $aud->id }}" class="bo-panel-subtle">
                                <td colspan="7" class="p-4">
                                    @php
                                        $typeStyle = match ($aud->tipo_audiencia) {
                                            'Lectura de Sentencia' => 'bo-section',
                                            'Audiencia Corta' => 'bo-section',
                                            default => 'bo-section',
                                        };
                                        $statusStyle = match ($aud->estado) {
                                            'FINALIZADA' => 'bo-chip bo-chip--ok',
                                            'EN_CURSO' => 'bo-chip bo-chip--info',
                                            'RECESO' => 'bo-chip bo-chip--warn',
                                            default => 'bo-chip',
                                        };
                                        $value = static fn (mixed $field): string => filled($field) ? (string) $field : 'Sin registro';
                                        $juecesInhabilitados = collect($aud->jueces_inhabilitados ?? [])
                                            ->map(fn ($juez) => is_array($juez) ? ($juez['nombre_completo'] ?? null) : $juez)
                                            ->filter()
                                            ->join(', ');
                                    @endphp

                                    <section id="audiencia-detalle-{{ $aud->id }}" aria-label="Detalle de audiencia {{ $aud->rit }}"
                                        class="overflow-hidden border bo-border border-l-4 rounded-lg bo-panel shadow-sm {{ $typeStyle }}">
                                        <header class="flex flex-col gap-3 px-4 py-3 border-b bo-border bo-panel sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-xs font-semibold tracking-wide bo-muted uppercase">Ficha de audiencia</p>
                                                <h3 class="mt-1 text-base font-semibold bo-strong">{{ $value($aud->rit) }} <span class="font-normal ">·</span> {{ $value($aud->tipo_audiencia) }}</h3>
                                                <p class="mt-1 text-sm bo-muted">Sala {{ $value($aud->sala) }} · {{ $aud->hora_inicio?->format('H:i') ?? 'Sin registro' }} · {{ $value($aud->ubicacion) }}</p>
                                            </div>
                                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">{{ $value($aud->estado) }}</span>
                                        </header>

                                        <div class="grid gap-6 p-4 text-sm bo-strong lg:grid-cols-2">
                                            <section aria-labelledby="causa-{{ $aud->id }}">
                                                <h4 id="causa-{{ $aud->id }}" class="mb-3 text-xs font-semibold tracking-wide bo-muted uppercase">Causa y coordinación</h4>
                                                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                                    <div><dt class="text-xs bo-muted">RUC</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->ruc) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Encargado causa</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->encargado_sala ?? $aud->encargado_causa) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Acta</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->acta) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Cuenta Zoom</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->cta_zoom) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Anfitrión</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->anfitrion) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Duración</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->duracion) }}</dd></div>
                                                    <div class="sm:col-span-2"><dt class="text-xs bo-muted">Delito</dt><dd class="mt-0.5 font-medium bo-strong break-words">{{ $value($aud->delito) }}</dd></div>
                                                </dl>
                                            </section>

                                            <section aria-labelledby="integracion-{{ $aud->id }}">
                                                <h4 id="integracion-{{ $aud->id }}" class="mb-3 text-xs font-semibold tracking-wide bo-muted uppercase">Integración</h4>
                                                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                                                    <div><dt class="text-xs bo-muted">Juez presidente</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->JuezP) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Juez redactor</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->JuezR) }}</dd></div>
                                                    <div><dt class="text-xs bo-muted">Juez integrante</dt><dd class="mt-0.5 font-medium bo-strong">{{ $value($aud->JuezI) }}</dd></div>
                                                    <div class="sm:col-span-3"><dt class="text-xs bo-muted">Jueces inhabilitados</dt><dd class="mt-0.5 font-medium bo-strong break-words">{{ $value($juecesInhabilitados) }}</dd></div>
                                                </dl>
                                            </section>

                                            <section class="lg:col-span-2" aria-labelledby="participantes-{{ $aud->id }}">
                                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                    <h4 id="participantes-{{ $aud->id }}" class="text-xs font-semibold tracking-wide bo-muted uppercase">Acusado(s)</h4>
                                                    <p class="text-xs bo-muted">Testigos: <span class="font-semibold bo-strong">{{ $value($aud->num_testigos) }}</span> · Peritos: <span class="font-semibold bo-strong">{{ $value($aud->num_peritos) }}</span></p>
                                                </div>
                                                <div class="divide-y overflow-hidden border bo-border rounded-md">
                                                    @forelse ($aud->acusados ?? [] as $acusado)
                                                        <div class="flex flex-col gap-2 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                                                            <div>
                                                                <p class="font-medium bo-strong">{{ $acusado['nombre_completo'] ?? 'Sin registro' }}</p>
                                                                <p class="text-xs bo-muted">Medida: {{ $value($acusado['medida_cautelar'] ?? null) }} · Notificación: {{ $value($acusado['forma_notificacion'] ?? null) }}</p>
                                                            </div>
                                                            <span class="inline-flex w-fit rounded-full px-2 py-0.5 text-xs font-medium bo-strong">{{ $value($acusado['situacion'] ?? null) }}</span>
                                                        </div>
                                                    @empty
                                                        <p class="px-3 py-2.5 bo-muted">Sin registro</p>
                                                    @endforelse
                                                </div>
                                            </section>

                                            <section class="pt-4 border-t bo-border lg:col-span-2" aria-labelledby="observaciones-{{ $aud->id }}">
                                                <h4 id="observaciones-{{ $aud->id }}" class="mb-2 text-xs font-semibold tracking-wide bo-muted uppercase">Observaciones</h4>
                                                <p class="leading-6 bo-strong break-words">{{ $value($aud->obs) }}</p>
                                            </section>
                                        </div>
                                    </section>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @script
    <script>
        document.addEventListener('audiencia-eliminada', () => {
            SwalToast.fire({ icon: 'success', title: 'Audiencia eliminada.' });
        });
    </script>
    @endscript
</div>
