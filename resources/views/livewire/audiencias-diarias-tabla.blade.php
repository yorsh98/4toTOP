<div>
    <div class="mb-3 text-sm text-gray-600">
        Mostrando audiencias del <span class="font-semibold">{{ \Carbon\Carbon::parse($fecha)->format('d-m-Y') }}</span>.
        Total: <span class="font-semibold">{{ count($audiencias) }}</span>
    </div>

    @if (count($audiencias) === 0)
        <div class="p-4 text-sm border rounded-md bg-amber-50 text-amber-800">
            No se encontraron audiencias para la fecha seleccionada.
        </div>
    @else
        <div class="overflow-x-auto border rounded-md" wire:loading.class="opacity-50" wire:target="fecha, toggleDetalle, eliminar">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-3 py-2 text-right">Acciones</th>
                        <th class="px-3 py-2">Sala</th>
                        <th class="px-3 py-2">RIT</th>
                        <th class="px-3 py-2">RUC</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">Hora</th>
                        <th class="px-3 py-2">Encargado causa</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($audiencias as $aud)
                        <tr wire:key="audiencia-{{ $aud->id }}">
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <button type="button" wire:click="toggleDetalle({{ $aud->id }})"
                                    aria-expanded="{{ $detalleId === $aud->id ? 'true' : 'false' }}"
                                    aria-controls="audiencia-detalle-{{ $aud->id }}"
                                    aria-label="{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}"
                                    title="{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}"
                                    class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md text-blue-700 transition-all duration-150 ease-out motion-safe:hover:-translate-y-0.5 hover:bg-blue-50 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500">
                                    <i class="fas {{ $detalleId === $aud->id ? 'fa-chevron-up' : 'fa-chevron-down' }}" aria-hidden="true"></i>
                                    <span class="sr-only">{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}</span>
                                    <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">{{ $detalleId === $aud->id ? 'Ocultar detalle' : 'Ver detalle' }}</span>
                                </button>
                                @can('audiencias.editar')
                                    <button type="button" wire:click="editar({{ $aud->id }})"
                                        aria-label="Editar audiencia"
                                        title="Editar audiencia"
                                        class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md text-amber-700 transition-all duration-150 ease-out motion-safe:hover:-translate-y-0.5 hover:bg-amber-50 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                        <span class="sr-only">Editar audiencia</span>
                                        <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">Editar audiencia</span>
                                    </button>
                                @endcan
                                <button type="button" class="group relative inline-flex h-8 w-8 items-center justify-center rounded-md text-red-600 transition-all duration-150 ease-out motion-safe:hover:-translate-y-0.5 hover:bg-red-50 hover:shadow-sm focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                                    aria-label="Eliminar audiencia {{ $aud->rit }}"
                                    title="Eliminar audiencia"
                                    x-on:click="confirmDestructiveAction('¿Eliminar audiencia?', 'RIT {{ $aud->rit }}. Esta acción no se puede deshacer.').then(result => { if (result.isConfirmed) $wire.eliminar({{ $aud->id }}) })">
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                    <span class="sr-only">Eliminar audiencia</span>
                                    <span role="tooltip" class="pointer-events-none absolute bottom-full left-1/2 z-20 mb-2 -translate-x-1/2 whitespace-nowrap rounded bg-slate-800 px-2 py-1 text-xs font-medium text-white opacity-0 shadow-sm transition-opacity duration-150 group-hover:opacity-100 group-focus-visible:opacity-100">Eliminar audiencia</span>
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
                            <tr wire:key="audiencia-detalle-{{ $aud->id }}" class="bg-gray-50">
                                <td colspan="7" class="p-4">
                                    @php
                                        $typeStyle = match ($aud->tipo_audiencia) {
                                            'Lectura de Sentencia' => 'border-l-purple-500 bg-purple-50 text-purple-800',
                                            'Audiencia Corta' => 'border-l-emerald-500 bg-emerald-50 text-emerald-800',
                                            default => 'border-l-blue-500 bg-blue-50 text-blue-800',
                                        };
                                        $statusStyle = match ($aud->estado) {
                                            'FINALIZADA' => 'bg-emerald-100 text-emerald-800',
                                            'EN_CURSO' => 'bg-blue-100 text-blue-800',
                                            'RECESO' => 'bg-amber-100 text-amber-800',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                        $value = static fn (mixed $field): string => filled($field) ? (string) $field : 'Sin registro';
                                        $juecesInhabilitados = collect($aud->jueces_inhabilitados ?? [])
                                            ->map(fn ($juez) => is_array($juez) ? ($juez['nombre_completo'] ?? null) : $juez)
                                            ->filter()
                                            ->join(', ');
                                    @endphp

                                    <section id="audiencia-detalle-{{ $aud->id }}" aria-label="Detalle de audiencia {{ $aud->rit }}"
                                        class="overflow-hidden border border-gray-200 border-l-4 rounded-lg bg-white shadow-sm {{ $typeStyle }}">
                                        <header class="flex flex-col gap-3 px-4 py-3 border-b border-gray-200 bg-white sm:flex-row sm:items-center sm:justify-between">
                                            <div>
                                                <p class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Ficha de audiencia</p>
                                                <h3 class="mt-1 text-base font-semibold text-gray-900">{{ $value($aud->rit) }} <span class="font-normal text-gray-400">·</span> {{ $value($aud->tipo_audiencia) }}</h3>
                                                <p class="mt-1 text-sm text-gray-600">Sala {{ $value($aud->sala) }} · {{ $aud->hora_inicio?->format('H:i') ?? 'Sin registro' }} · {{ $value($aud->ubicacion) }}</p>
                                            </div>
                                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusStyle }}">{{ $value($aud->estado) }}</span>
                                        </header>

                                        <div class="grid gap-6 p-4 text-sm text-gray-700 lg:grid-cols-2">
                                            <section aria-labelledby="causa-{{ $aud->id }}">
                                                <h4 id="causa-{{ $aud->id }}" class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">Causa y coordinación</h4>
                                                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-2">
                                                    <div><dt class="text-xs text-gray-500">RUC</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->ruc) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Encargado causa</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->encargado_sala ?? $aud->encargado_causa) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Acta</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->acta) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Cuenta Zoom</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->cta_zoom) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Anfitrión</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->anfitrion) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Duración</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->duracion) }}</dd></div>
                                                    <div class="sm:col-span-2"><dt class="text-xs text-gray-500">Delito</dt><dd class="mt-0.5 font-medium text-gray-900 break-words">{{ $value($aud->delito) }}</dd></div>
                                                </dl>
                                            </section>

                                            <section aria-labelledby="integracion-{{ $aud->id }}">
                                                <h4 id="integracion-{{ $aud->id }}" class="mb-3 text-xs font-semibold tracking-wide text-gray-500 uppercase">Integración</h4>
                                                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                                                    <div><dt class="text-xs text-gray-500">Juez presidente</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->JuezP) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Juez redactor</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->JuezR) }}</dd></div>
                                                    <div><dt class="text-xs text-gray-500">Juez integrante</dt><dd class="mt-0.5 font-medium text-gray-900">{{ $value($aud->JuezI) }}</dd></div>
                                                    <div class="sm:col-span-3"><dt class="text-xs text-gray-500">Jueces inhabilitados</dt><dd class="mt-0.5 font-medium text-gray-900 break-words">{{ $value($juecesInhabilitados) }}</dd></div>
                                                </dl>
                                            </section>

                                            <section class="lg:col-span-2" aria-labelledby="participantes-{{ $aud->id }}">
                                                <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                                    <h4 id="participantes-{{ $aud->id }}" class="text-xs font-semibold tracking-wide text-gray-500 uppercase">Acusado(s)</h4>
                                                    <p class="text-xs text-gray-500">Testigos: <span class="font-semibold text-gray-700">{{ $value($aud->num_testigos) }}</span> · Peritos: <span class="font-semibold text-gray-700">{{ $value($aud->num_peritos) }}</span></p>
                                                </div>
                                                <div class="divide-y overflow-hidden border border-gray-200 rounded-md">
                                                    @forelse ($aud->acusados ?? [] as $acusado)
                                                        <div class="flex flex-col gap-2 px-3 py-2.5 sm:flex-row sm:items-center sm:justify-between">
                                                            <div>
                                                                <p class="font-medium text-gray-900">{{ $acusado['nombre_completo'] ?? 'Sin registro' }}</p>
                                                                <p class="text-xs text-gray-500">Medida: {{ $value($acusado['medida_cautelar'] ?? null) }} · Notificación: {{ $value($acusado['forma_notificacion'] ?? null) }}</p>
                                                            </div>
                                                            <span class="inline-flex w-fit rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">{{ $value($acusado['situacion'] ?? null) }}</span>
                                                        </div>
                                                    @empty
                                                        <p class="px-3 py-2.5 text-gray-500">Sin registro</p>
                                                    @endforelse
                                                </div>
                                            </section>

                                            <section class="pt-4 border-t border-gray-200 lg:col-span-2" aria-labelledby="observaciones-{{ $aud->id }}">
                                                <h4 id="observaciones-{{ $aud->id }}" class="mb-2 text-xs font-semibold tracking-wide text-gray-500 uppercase">Observaciones</h4>
                                                <p class="leading-6 text-gray-700 break-words">{{ $value($aud->obs) }}</p>
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
