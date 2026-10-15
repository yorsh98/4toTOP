<x-slot:header>
    <x-page-heading eyebrow="Visita Carcel · Catalogo" title="Visita Carcel" />
</x-slot:header>

<div class="py-2">
    <style>
        [aria-labelledby="ficha-causa-title"] table tbody td:last-child { white-space: nowrap; }

        .reporte-control-scroll {
            overscroll-behavior: contain;
            scrollbar-gutter: stable;
        }

        @media (min-width: 1024px) {
            .reporte-control-resumen {
                height: min(32rem, calc(100dvh - 22rem));
                min-height: 20rem;
            }

            .reporte-control-columna {
                display: flex;
                min-height: 0;
                flex-direction: column;
                overflow: hidden;
            }

            .reporte-control-scroll {
                min-height: 0;
                max-height: none;
                flex: 1;
                overflow-y: auto;
            }
        }
    </style>
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:px-8">
        <x-flash-notifications />

        <div class="overflow-x-auto pb-1">
            <div class="mx-auto flex min-w-max shrink-0 items-center rounded-2xl border border-slate-200 bg-white p-1.5 shadow-sm sm:w-fit">
                <button type="button" wire:click="seleccionarModo('comparacion')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition sm:px-6', 'bg-[#0A2E5D] text-white shadow-sm' => $modo === 'comparacion', 'text-slate-600 hover:bg-slate-50' => $modo !== 'comparacion'])>Importar archivos</button>
                <button type="button" wire:click="seleccionarModo('alimentacion')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition sm:px-6', 'bg-rose-700 text-white shadow-sm' => $modo === 'alimentacion', 'text-slate-600 hover:bg-slate-50' => $modo !== 'alimentacion'])>Exportar visita de cárcel</button>
                @can('visita_carcel.reportes')<button type="button" wire:click="seleccionarModo('reportes')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition sm:px-6', 'bg-emerald-700 text-white shadow-sm' => $modo === 'reportes', 'text-slate-600 hover:bg-slate-50' => $modo !== 'reportes'])>Reportes de control</button>@endcan
                <button type="button" wire:click="seleccionarModo('notificaciones')" @class(['rounded-xl px-4 py-2 text-sm font-semibold transition sm:px-6', 'bg-amber-700 text-white shadow-sm' => $modo === 'notificaciones', 'text-slate-600 hover:bg-slate-50' => $modo !== 'notificaciones'])>Plazo revisión de PP</button>
            </div>
        </div>

        @if ($modo === 'alimentacion')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_420px]">
                    <div class="border-l-4 border-rose-700 px-6 py-7 sm:px-8 sm:py-9">
                        <div class="flex items-start gap-4">
                            <span class="hidden size-12 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-xl text-rose-700 sm:inline-flex"><i class="fa-solid fa-file-zipper" aria-hidden="true"></i></span>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-rose-700">Exportación del catálogo</p>
                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 font-mono text-[10px] font-bold text-emerald-700">{{ $totalPenales }} penales activos</span>
                                </div>
                                <h3 class="mt-2 text-2xl font-semibold tracking-tight text-slate-900">Visita de cárcel por establecimiento</h3>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Genera un archivo ZIP con una planilla de imputados activos por cada establecimiento penitenciario.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col justify-center border-t border-slate-200 bg-[#0A2E5D] p-6 text-white lg:border-l lg:border-t-0 sm:p-8">
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-sky-200">Archivo de salida</p>
                        <p class="mt-2 text-lg font-semibold">Planillas por penal</p>
                        <p class="mt-1 text-sm leading-5 text-slate-300">Formato ZIP con archivos Excel listos para distribución.</p>
                        <a href="{{ route('visita-carcel.exportar-penales') }}" class="mt-5 inline-flex items-center justify-center gap-2 rounded-lg bg-white px-4 py-3 text-sm font-semibold text-[#0A2E5D] shadow-sm transition hover:bg-sky-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-white"><i class="fa-solid fa-download" aria-hidden="true"></i> Descargar planillas</a>
                    </div>
                </div>

                <div class="flex flex-col gap-3 border-t border-slate-200 bg-slate-50/70 px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-8">
                    <div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Catálogo de penales</p><p class="mt-1 text-sm text-slate-600">{{ $totalPenales }} establecimientos incluidos en la exportación.</p></div>
                    <button type="button" wire:click="abrirGestionPenales" class="self-start rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 sm:self-auto"><i class="fa-solid fa-building-shield mr-2" aria-hidden="true"></i>Gestionar penales</button>
                </div>

                @if ($resultadoActualizacion !== [])
                    <div class="border-t border-slate-200 lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
                        <div class="shrink-0 border-b border-slate-100 bg-slate-50/70 p-5 sm:px-6">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Resultado de la carga</p>
                                    <p class="mt-1 text-sm text-slate-600">Cada archivo conserva su propio detalle.</p>
                                </div>
                                <span class="rounded-full bg-rose-50 px-3 py-1.5 text-xs font-semibold text-rose-700">{{ $resultadoActualizacion['archivos_procesados'] }} procesados</span>
                            </div>

                            <div class="mt-4 grid grid-cols-2 gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 sm:grid-cols-4 lg:grid-cols-8">
                                @foreach ([
                                    ['Procesadas', $resultadoActualizacion['procesadas'], 'text-slate-900'],
                                    ['Actualizadas', $resultadoActualizacion['actualizadas'], 'text-emerald-700'],
                                    ['Causas nuevas', $resultadoActualizacion['causas_creadas'], 'text-sky-700'],
                                    ['Imputados nuevos', $resultadoActualizacion['imputados_creados'], 'text-sky-700'],
                                    ['Sin coincidencia', $resultadoActualizacion['sin_coincidencia'], 'text-amber-700'],
                                    ['Sin cambios', $resultadoActualizacion['sin_cambios'], 'text-slate-600'],
                                    ['Omitidos', $resultadoActualizacion['archivos_omitidos'], 'text-rose-700'],
                                    ['Procesados', $resultadoActualizacion['archivos_procesados'], 'text-[#0A2E5D]'],
                                ] as [$etiqueta, $valor, $color])
                                    <div class="bg-white px-3 py-2.5">
                                        <p class="font-mono text-[9px] font-bold uppercase tracking-[0.1em] text-slate-500">{{ $etiqueta }}</p>
                                        <p @class(['mt-1 text-xl font-semibold tabular-nums', $color])>{{ $valor }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="overflow-x-auto lg:min-h-0 lg:flex-1 lg:overflow-y-auto">
                            <table class="min-w-[760px] w-full text-sm">
                                <thead class="sticky top-0 z-10 bg-slate-100 text-left shadow-sm">
                                    <tr>
                                        <th class="w-[42%] px-5 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Archivo</th>
                                        <th class="px-3 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Estado</th>
                                        <th class="px-3 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Procesadas</th>
                                        <th class="px-3 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Creadas</th>
                                        <th class="px-3 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Actualizadas</th>
                                        <th class="w-[24%] px-5 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Detalle</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($resultadoActualizacion['archivos'] as $archivo)
                                        <tr class="align-top hover:bg-slate-50/70">
                                            <td class="px-5 py-3.5">
                                                <span class="block max-w-[360px] truncate font-medium text-slate-800" title="{{ $archivo['archivo'] }}">{{ $archivo['archivo'] }}</span>
                                            </td>
                                            <td class="px-3 py-3.5">
                                                <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', $archivo['error'] ? 'bg-rose-50 text-rose-700' : 'bg-emerald-50 text-emerald-700'])>{{ $archivo['error'] ? 'Omitido' : 'Procesado' }}</span>
                                            </td>
                                            <td class="px-3 py-3.5 text-right font-mono text-xs tabular-nums text-slate-700">{{ $archivo['procesadas'] }}</td>
                                            <td class="px-3 py-3.5 text-right font-mono text-xs tabular-nums text-sky-700">{{ $archivo['causas_creadas'] + $archivo['imputados_creados'] }}</td>
                                            <td class="px-3 py-3.5 text-right font-mono text-xs tabular-nums text-emerald-700">{{ $archivo['actualizadas'] }}</td>
                                            <td class="px-5 py-3.5 text-xs leading-5 text-rose-700">{{ $archivo['error'] }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </section>
            @if ($informacionAlimentacionAbierta)
                @teleport('body')
                <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="formato-alimentacion-title"><section class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-rose-700">Actualización de planillas</p><h3 id="formato-alimentacion-title" class="mt-1 text-xl font-semibold text-slate-900">Formato requerido para alimentar el catálogo</h3></div><button type="button" wire:click="cerrarInformacionAlimentacion" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header><div class="space-y-5 p-5 sm:p-7"><div class="grid gap-3 text-sm text-slate-700 sm:grid-cols-2"><p><strong>Archivos:</strong> hasta 25 XLS, XLSX o ZIP.</p><p><strong>Tamaño:</strong> máximo 20 MB por archivo.</p><p><strong>Hojas:</strong> se procesan todas las hojas del archivo.</p><p><strong>Límite:</strong> 5.000 filas y 70 columnas por hoja.</p></div><section class="rounded-xl border border-rose-100 bg-rose-50/50 p-4"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-rose-700">Columnas obligatorias</p><div class="mt-3 overflow-x-auto"><table class="min-w-[720px] w-full border-collapse text-left text-xs"><thead><tr class="bg-white"><th class="border border-rose-100 px-2 py-2">RIT</th><th class="border border-rose-100 px-2 py-2">AÑO</th><th class="border border-rose-100 px-2 py-2">RUC</th><th class="border border-rose-100 px-2 py-2">RUT</th><th class="border border-rose-100 px-2 py-2">PLAZO CIERRE</th><th class="border border-rose-100 px-2 py-2">ULTIMA REV. P.P.</th><th class="border border-rose-100 px-2 py-2">JUEZ</th><th class="border border-rose-100 px-2 py-2">DEFENSOR</th><th class="border border-rose-100 px-2 py-2">FEC. ULT. RES.</th><th class="border border-rose-100 px-2 py-2">ULT. RESOLUCIÓN</th></tr></thead></table></div><p class="mt-3 text-sm text-slate-700">Para crear un imputado que no existe, la planilla también debe incluir <strong>IMPUTADO</strong>, <strong>DELITO</strong> y <strong>FEC. INGRESO P.P.</strong>. El penal se identifica desde el nombre de la hoja o su encabezado.</p></section><section><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Errores frecuentes</p><ul class="mt-2 space-y-1 text-sm text-slate-700"><li>No se detectan las columnas obligatorias dentro de las primeras 10 filas.</li><li>El penal no puede identificarse desde la hoja.</li><li>El RUC, RIT o RUT no coincide con el catálogo.</li><li>El archivo está dañado, protegido o excede los límites.</li></ul></section></div><footer class="flex justify-end border-t border-slate-200 px-5 py-4 sm:px-7"><button type="button" wire:click="cerrarInformacionAlimentacion" class="rounded-lg bg-rose-700 px-4 py-2 text-sm font-semibold text-white">Entendido</button></footer></section></div>
                @endteleport
            @endif
            @if ($gestionPenalesAbierta)
                @teleport('body')
                <div class="fixed inset-0 z-[60] bg-slate-950/40" role="dialog" aria-modal="true" aria-labelledby="gestion-penales-title">
                    <section class="absolute inset-y-0 right-0 flex w-full max-w-xl flex-col overflow-hidden bg-white shadow-2xl">
                        <header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7">
                            <div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-rose-700">Exportación</p><h3 id="gestion-penales-title" class="mt-1 text-xl font-semibold text-slate-900">Penales activos</h3><p class="mt-1 text-sm text-slate-600">Define qué establecimientos forman parte del libro Santiago.</p></div>
                            <button type="button" wire:click="cerrarGestionPenales" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button>
                        </header>
                        <div class="flex-1 overflow-y-auto p-5 sm:p-7">
                            <label for="buscar-penal" class="sr-only">Buscar penal</label>
                            <div class="relative"><i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i><input id="buscar-penal" type="search" wire:model.live.debounce.300ms="searchPenales" placeholder="Buscar penal activo" class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm"></div>
                            <div class="mt-4 divide-y divide-slate-100 rounded-xl border border-slate-200">
                                @forelse ($penalesGestion as $penal)
                                    <div wire:key="penal-{{ $penal->id }}" class="flex items-center justify-between gap-3 px-4 py-3">
                                        <div class="min-w-0"><span class="block truncate text-sm font-medium text-slate-800">{{ $penal->nombre }}</span>@if ($penal->exportar_en_santiago)<span class="mt-1 inline-flex rounded-full bg-sky-50 px-2 py-0.5 font-mono text-[9px] font-bold uppercase tracking-wider text-[#0A2E5D]">Libro Santiago</span>@endif</div>
                                        @can('visita_carcel.gestionar')
                                            <div class="flex shrink-0 items-center gap-2">
                                                <button type="button" wire:click="alternarExportacionSantiago({{ $penal->id }})" @class(['rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition', $penal->exportar_en_santiago ? 'border-sky-200 bg-sky-50 text-[#0A2E5D] hover:bg-sky-100' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50'])>{{ $penal->exportar_en_santiago ? 'Retirar' : 'Incluir' }}</button>
                                                <button type="button" wire:click="eliminarPenal({{ $penal->id }})" wire:confirm="El penal dejará de estar disponible para nuevas importaciones y exportaciones. ¿Continuar?" class="inline-flex size-8 items-center justify-center rounded-lg border border-red-100 text-red-600 hover:bg-red-600 hover:text-white" title="Eliminar penal" aria-label="Eliminar {{ $penal->nombre }}"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>
                                            </div>
                                        @endcan
                                    </div>
                                @empty
                                    <p class="px-4 py-8 text-center text-sm text-slate-500">No hay penales activos que coincidan.</p>
                                @endforelse
                            </div>
                            @can('visita_carcel.gestionar')
                                <form wire:submit="guardarPenal" class="mt-6 border-t border-slate-200 pt-5">
                                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-rose-700">Agregar penal</p>
                                    <div class="mt-3"><label for="nuevo-penal" class="sr-only">Nombre oficial del penal</label><input id="nuevo-penal" type="text" wire:model="nuevoPenal" placeholder="Ej. CDP Yumbel" class="w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm">@error('nuevoPenal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div>
                                    <label class="mt-3 flex cursor-pointer items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/60 p-3"><input type="checkbox" wire:model="nuevoPenalEnSantiago" class="mt-0.5 rounded border-slate-300 text-[#0A2E5D] focus:ring-[#0A2E5D]"><span><strong class="block text-sm text-slate-800">Incluir en Visita_Carcel_Santiago.xlsx</strong><span class="mt-0.5 block text-xs text-slate-600">El penal se exportará como una hoja dentro del libro metropolitano.</span></span></label>
                                    <button type="submit" class="mt-3 w-full rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-800">Agregar penal</button>
                                </form>
                            @endcan
                        </div>
                    </section>
                </div>
                @endteleport
            @endif
        @elseif ($modo === 'reportes')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-l-4 border-emerald-700 px-6 py-5">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-700">Bitácora del catálogo</p>
                            <h3 class="mt-1 text-xl font-semibold text-slate-900">Reportes de control</h3>
                            <p class="mt-1 text-sm text-slate-600">Altas, bajas y cambios del período seleccionado.</p>
                        </div>
                        <div class="grid grid-cols-2 gap-2 sm:flex sm:items-end">
                            <label class="text-xs font-medium text-slate-600">Desde<input type="date" wire:model.live="reporteDesde" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700"></label>
                            <label class="text-xs font-medium text-slate-600">Hasta<input type="date" wire:model.live="reporteHasta" class="mt-1 block w-full rounded-lg border-slate-300 text-sm focus:border-emerald-700 focus:ring-emerald-700"></label>
                        </div>
                    </div>
                </div>

                <div class="grid gap-px border-y border-slate-200 bg-slate-200 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([['Registros agregados', $reporte['agregados']->count(), 'text-sky-700'], ['Registros eliminados', $reporte['eliminados']->count(), 'text-rose-700'], ['Registros modificados', $reporte['modificados']->count(), 'text-amber-700'], ['Total presos activos', $reporte['total_imputados'], 'text-emerald-700']] as [$etiqueta, $valor, $color])
                        <div class="bg-white px-5 py-4"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">{{ $etiqueta }}</p><p @class(['mt-1 text-3xl font-semibold tabular-nums', $color])>{{ $valor }}</p></div>
                    @endforeach
                </div>

                <div class="reporte-control-resumen grid divide-y divide-slate-200 lg:grid-cols-[minmax(0,1fr)_minmax(340px,0.9fr)] lg:divide-x lg:divide-y-0">
                    <div class="reporte-control-columna p-5 sm:p-6">
                        <div class="flex items-center justify-between gap-3"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Presos por penal</p><p class="mt-1 text-sm text-slate-600">Distribución actual de imputados activos.</p></div><span class="rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">{{ $reporte['por_penal']->count() }} penales</span></div>
                        <div class="reporte-control-scroll mt-4 overflow-hidden overflow-y-auto rounded-xl border border-slate-200"><table class="w-full text-sm"><tbody class="divide-y divide-slate-100">@forelse ($reporte['por_penal'] as $penal)<tr><td class="px-4 py-3 font-medium text-slate-800">{{ $penal->penal }}</td><td class="px-4 py-3 text-right font-mono text-sm font-bold tabular-nums text-emerald-700">{{ $penal->total }}</td></tr>@empty<tr><td class="px-4 py-8 text-center text-sm text-slate-500">No hay imputados activos.</td></tr>@endforelse</tbody></table></div>
                    </div>

                    <div class="reporte-control-columna bg-slate-50/70 p-5 sm:p-6">
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-rose-700">Rollback de eliminados</p>
                        <p class="mt-1 text-sm text-slate-600">Restaurar una causa recupera también sus imputados eliminados asociados.</p>
                        <div class="reporte-control-scroll mt-4 max-h-72 space-y-2 overflow-y-auto pr-1">
                            @forelse ($reporte['eliminados'] as $registro)
                                <article class="flex items-center justify-between gap-3 rounded-xl border border-rose-100 bg-white p-3"><div class="min-w-0"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.1em] text-rose-700">{{ $registro['tipo'] }} · {{ $registro['fecha']->format('d/m/Y H:i') }}</p><p class="mt-1 truncate text-sm font-medium text-slate-800" title="{{ $registro['descripcion'] }}">{{ $registro['descripcion'] }}</p></div>@can('visita_carcel.gestionar')<button type="button" @if ($registro['tipo'] === 'Causa') wire:click="restaurarCausa({{ $registro['id'] }})" wire:confirm="Se restaurará la causa y sus imputados eliminados asociados. ¿Continuar?" @else wire:click="restaurarImputado({{ $registro['id'] }})" wire:confirm="Se restaurará este imputado. ¿Continuar?" @endif class="shrink-0 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">Restaurar</button>@endcan</article>
                            @empty
                                <p class="rounded-xl border border-dashed border-slate-300 bg-white px-4 py-8 text-center text-sm text-slate-500">No hay eliminaciones en este período.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="grid divide-y divide-slate-200 border-t border-slate-200 lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                    @foreach (['agregados' => ['Registros agregados', 'text-sky-700'], 'modificados' => ['Registros modificados', 'text-amber-700']] as $clave => [$titulo, $color])
                        <div class="p-5 sm:p-6"><p @class(['font-mono text-[10px] font-bold uppercase tracking-[0.14em]', $color])>{{ $titulo }}</p><div class="mt-3 max-h-52 space-y-2 overflow-y-auto pr-1">@forelse ($reporte[$clave] as $registro)<div class="rounded-lg border border-slate-200 bg-white px-3 py-2.5"><p class="text-sm font-medium text-slate-800">{{ $registro['descripcion'] }}</p><p class="mt-1 font-mono text-[10px] text-slate-500">{{ $registro['fecha']->format('d/m/Y H:i') }}@if (isset($registro['tipo'])) · {{ $registro['tipo'] }}@endif</p></div>@empty<p class="text-sm text-slate-500">No hay registros en este período.</p>@endforelse</div></div>
                    @endforeach
                </div>
            </section>
        @elseif ($modo === 'notificaciones')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
                <div class="shrink-0 border-l-4 border-amber-600 px-6 py-5">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-amber-700">Control de plazo legal</p>
                    <div class="mt-1 flex flex-wrap items-center justify-between gap-3"><div><h3 class="text-xl font-semibold text-slate-900">Revisión de prisión preventiva</h3><p class="mt-1 text-sm text-slate-600">Imputados con 160 días o más desde la última revisión de PP.</p></div><div class="flex w-full items-center gap-2 sm:w-auto"><div class="relative flex-1 sm:w-72"><i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i><input type="search" wire:model.live.debounce.300ms="searchNotificaciones" placeholder="Buscar imputado, RUT, RIT o RUC" class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-amber-700 focus:ring-amber-700"></div><span class="whitespace-nowrap rounded-full bg-amber-50 px-3 py-2.5 text-xs font-semibold text-amber-800">{{ $notificaciones->count() }} pendientes</span></div></div>
                </div>
                <div class="hidden overflow-x-auto md:block lg:min-h-0 lg:flex-1 lg:overflow-y-auto"><table class="min-w-[900px] w-full text-sm"><thead class="sticky top-0 z-10 bg-amber-50 text-left"><tr><th class="px-5 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Nombre imputado</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">RUT</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">RIT</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">RUC</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Última revisión PP</th><th class="px-4 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Día plazo</th><th class="px-5 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($notificaciones as $imputado)<tr wire:key="notificacion-{{ $imputado->id }}" class="hover:bg-amber-50/40"><td class="px-5 py-4 font-medium text-slate-900">{{ $imputado->nombre }}</td><td class="px-4 py-4 font-mono text-xs text-slate-600">{{ $imputado->rut }}</td><td class="px-4 py-4 font-mono text-xs text-slate-600">{{ $imputado->causa->rit }}</td><td class="px-4 py-4 font-mono text-xs text-slate-600">{{ $imputado->causa->ruc }}</td><td class="px-4 py-4 font-mono text-xs text-slate-600">{{ $imputado->ultima_rev_pp->format('d/m/Y') }}</td><td class="px-4 py-4 text-right"><span @class(['inline-flex rounded-full px-2.5 py-1 font-mono text-xs font-bold tabular-nums', $imputado->dias_plazo >= 180 ? 'bg-red-100 text-red-700' : ($imputado->dias_plazo >= 170 ? 'bg-orange-100 text-orange-700' : 'bg-amber-100 text-amber-800')])>{{ $imputado->dias_plazo > 180 ? '180+' : $imputado->dias_plazo }}</span></td><td class="whitespace-nowrap px-5 py-4 text-right"><button type="button" wire:click="verFicha({{ $imputado->causa->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:border-amber-300 hover:bg-amber-50">Ver ficha</button>@can('visita_carcel.gestionar')<button type="button" wire:click="abrirFormularioRevisionPp({{ $imputado->id }})" class="ml-2 rounded-lg bg-amber-700 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-800">Actualizar</button>@endcan</td></tr>@empty<tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No se encuentran imputados para la revisión de PP.</td></tr>@endforelse</tbody></table></div>
                <div class="divide-y divide-slate-100 md:hidden">@forelse ($notificaciones as $imputado)<article wire:key="notificacion-mobile-{{ $imputado->id }}" class="space-y-2 p-5"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $imputado->nombre }}</p><p class="font-mono text-xs text-slate-500">{{ $imputado->rut }} · {{ $imputado->causa->rit }}</p></div><span @class(['rounded-full px-2.5 py-1 font-mono text-xs font-bold', $imputado->dias_plazo >= 180 ? 'bg-red-100 text-red-700' : ($imputado->dias_plazo >= 170 ? 'bg-orange-100 text-orange-700' : 'bg-amber-100 text-amber-800')])>Día {{ $imputado->dias_plazo > 180 ? '180+' : $imputado->dias_plazo }}</span></div><p class="text-xs text-slate-600">RUC {{ $imputado->causa->ruc }} · Última revisión: {{ $imputado->ultima_rev_pp->format('d/m/Y') }}</p><div><button type="button" wire:click="verFicha({{ $imputado->causa->id }})" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Ver ficha</button>@can('visita_carcel.gestionar')<button type="button" wire:click="abrirFormularioRevisionPp({{ $imputado->id }})" class="ml-2 rounded-lg bg-amber-700 px-3 py-2 text-xs font-semibold text-white">Actualizar</button>@endcan</div></article>@empty<p class="px-6 py-12 text-center text-sm text-slate-500">No se encuentran imputados para la revisión de PP.</p>@endforelse</div>
            </section>
        @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
            <div class="shrink-0 flex flex-col gap-4 border-l-4 border-[#0A2E5D] px-6 py-5 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-[#0A2E5D]">Registro activo</p>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Comparacion de causas</h3>
                </div>
                <div class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end lg:w-auto">
                    <div class="relative flex-1 lg:w-80">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar RUC, RIT o imputado" class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-rose-700 focus:ring-rose-700">
                    </div>
                    <span class="inline-flex items-center justify-center rounded-full bg-rose-50 px-3 py-2.5 text-xs font-semibold text-rose-700">Total: {{ $totalImputados }}</span>
                    @can('visita_carcel.gestionar')
                        @if ($coincidenciasCruce !== [])<a href="{{ route('visita-carcel.exportar-por-penal') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Exportar comparacion</a>@endif
                        @if ($resultadoImportacion !== [])<button type="button" wire:click="salirModoCruce" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Salir de comparacion</button>@endif
                        <button type="button" wire:click="abrirFormularioCausa" class="inline-flex items-center justify-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-800 transition hover:bg-rose-100"><i class="fa-solid fa-plus" aria-hidden="true"></i> Nueva causa</button>
                        <button type="button" wire:click="abrirInformacionImportacion" class="inline-flex size-11 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-[#0A2E5D] transition hover:bg-sky-100" title="Ver formato requerido" aria-label="Ver formato requerido"><i class="fa-solid fa-circle-info text-lg" aria-hidden="true"></i></button>
                        <button type="button" wire:click="abrirFormularioImportacion" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0A2E5D] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i> Importar para comparar</button>
                    @endcan
                </div>
            </div>

        @if ($formularioImportacionAbierto)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-l-4 border-sky-700 px-5 py-3 sm:px-6"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-sky-700">Comparacion temporal</p><button type="button" wire:click="cerrarFormularioImportacion" class="inline-flex size-8 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar formulario"><i class="fa-solid fa-xmark text-base" aria-hidden="true"></i></button></div>
                <div class="border-t border-slate-100 bg-slate-50 p-5 sm:p-7">
                    <form wire:submit="importarMovimientos" class="flex flex-col gap-4 sm:flex-row sm:items-end"><div class="sm:w-56"><label class="mb-1.5 block text-sm font-medium text-slate-700">Formato</label><select wire:model="tipoImportacion" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm"><option value="simple">Comparación simple</option><option value="libro">Libro Visita Cárcel</option></select></div><div x-data="{ archivo: '' }" class="flex-1"><label class="mb-1.5 block text-sm font-medium text-slate-700">Archivo XLSX o XLS</label><div class="flex items-center gap-3 rounded-lg border border-slate-300 bg-white p-1.5"><input id="archivo-movimientos" x-ref="archivo" type="file" wire:model="archivoMovimientos" accept=".xlsx,.xls" class="sr-only" @change="archivo = $refs.archivo.files[0]?.name ?? ''"><label for="archivo-movimientos" tabindex="0" @keydown.enter.prevent="$refs.archivo.click()" @keydown.space.prevent="$refs.archivo.click()" class="cursor-pointer rounded-md bg-slate-200 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0A2E5D]">Seleccionar archivo</label><span class="min-w-0 truncate text-sm text-slate-600" x-text="archivo || 'Ningún archivo seleccionado'"></span></div>@error('archivoMovimientos') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><button type="submit" class="rounded-lg bg-[#0A2E5D] px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="importarMovimientos,archivoMovimientos">Importar y contrastar</button></form>
                    @if ($resultadoImportacion !== [])<div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><div class="rounded-lg border border-slate-200 bg-white p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-500">Procesadas</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ $resultadoImportacion['filas_procesadas'] }}</p></div><div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-emerald-700">Movimientos asociados</p><p class="mt-1 text-xl font-semibold text-emerald-800">{{ $resultadoImportacion['movimientos_creados'] }}</p></div><div class="rounded-lg border border-amber-100 bg-amber-50 p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-amber-700">Sin causa o imputado</p><p class="mt-1 text-xl font-semibold text-amber-800">{{ $resultadoImportacion['causas_no_encontradas'] + $resultadoImportacion['imputados_no_encontrados'] }}</p></div><div class="rounded-lg border border-red-100 bg-red-50 p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-red-700">Inconsistencias o ambiguas</p><p class="mt-1 text-xl font-semibold text-red-800">{{ $resultadoImportacion['inconsistencias'] + $resultadoImportacion['coincidencias_ambiguas'] }}</p></div></div>@endif
                    @if ($resultadoImportacion['errores'] ?? [])<section class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-amber-800">Filas no procesadas</p><ul class="mt-2 space-y-1 text-sm text-amber-900">@foreach ($resultadoImportacion['errores'] as $error)<li>{{ $error }}</li>@endforeach</ul>@if (count($resultadoImportacion['errores']) === 20)<p class="mt-2 text-xs text-amber-800">Se muestran los primeros 20 motivos.</p>@endif</section>@endif
                    @if ($causasPendientes !== [])<section class="mt-5 flex flex-col gap-3 border-l-4 border-amber-600 bg-amber-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-3"><span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-700 font-mono text-sm font-bold text-white">{{ count($causasPendientes) }}</span><p class="text-sm font-semibold text-amber-950">Causas no ingresadas en este sistema</p></div><button type="button" wire:click="abrirRevisionCausasPendientes" class="self-start rounded-lg bg-amber-700 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-800 sm:self-auto">Revisar causas</button></section>@endif
                    @if ($imputadosPendientes !== [])<section class="mt-3 flex flex-col gap-3 border-l-4 border-sky-700 bg-sky-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"><div class="flex items-center gap-3"><span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-[#0A2E5D] font-mono text-sm font-bold text-white">{{ count($imputadosPendientes) }}</span><p class="text-sm font-semibold text-slate-900">Imputados pendientes en causas existentes</p></div><button type="button" wire:click="abrirRevisionImputadosPendientes" class="self-start rounded-lg bg-[#0A2E5D] px-3 py-2 text-sm font-semibold text-white hover:bg-sky-700 sm:self-auto">Revisar imputados</button></section>@endif
                    @if ($comparacionAusenciasDisponible)
                        <section class="mt-3 flex flex-col gap-3 border-l-4 border-rose-700 bg-rose-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <span class="inline-flex size-9 shrink-0 items-center justify-center rounded-full bg-rose-700 font-mono text-sm font-bold text-white" role="status">{{ $causasAusentes->total() }}</span>
                                <p class="text-sm font-semibold text-rose-950">Causas no presentes en el archivo</p>
                            </div>
                            <button type="button" wire:click="abrirRevisionCausasAusentes" class="self-start rounded-lg bg-rose-700 px-3 py-2 text-sm font-semibold text-white hover:bg-rose-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700 sm:self-auto">Revisar causas ausentes</button>
                        </section>
                    @elseif ($avisoComparacionAusencias !== '')
                        <p class="mt-3 border-l-4 border-amber-600 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-900" role="status">{{ $avisoComparacionAusencias }}</p>
                    @endif
                </div>
            </section>
        @endif

        @if ($revisionCausasAusentesAbierta && $comparacionAusenciasDisponible)
            @teleport('body')
            <div class="fixed inset-0 z-[60] bg-slate-950/40" role="dialog" aria-modal="true" aria-labelledby="causas-ausentes-title">
                <section class="absolute inset-y-0 right-0 flex w-full max-w-3xl flex-col overflow-hidden bg-white shadow-2xl">
                    <header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-rose-700">Baja pendiente</p>
                            <h3 id="causas-ausentes-title" class="mt-1 text-xl font-semibold text-slate-900">Causas no presentes en el archivo</h3>
                            <p class="mt-1 text-sm text-slate-600">Revise las {{ $causasAusentes->total() }} causas antes de eliminarlas del catálogo.</p>
                        </div>
                        <button type="button" wire:click="cerrarRevisionCausasAusentes" class="inline-flex size-9 shrink-0 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar causas ausentes"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button>
                    </header>
                    <div class="flex-1 space-y-3 overflow-y-auto p-5 sm:p-7">
                        <details class="rounded-xl border border-rose-100 bg-rose-50/60 p-4 text-xs leading-5 text-slate-600">
                            <summary class="cursor-pointer font-semibold text-rose-800">Comparación con todo el catálogo activo</summary>
                            <p class="mt-2">El archivo debe contener el listado completo. Si corresponde solo a algunos penales, la ausencia de una causa no confirma que deba eliminarse. Los RUC presentes con un RIT distinto se informan como inconsistencias.</p>
                        </details>
                        @forelse ($causasAusentes as $causaAusente)
                            <article wire:key="causa-ausente-{{ $causaAusente->id }}" class="rounded-xl border border-slate-200">
                                <div class="border-b border-slate-100 p-4">
                                    <strong class="block text-slate-900">RUC {{ $causaAusente->ruc }} · RIT {{ $causaAusente->rit }}</strong>
                                    <p class="mt-1 text-xs text-slate-500">No encontrada en el archivo · {{ $causaAusente->imputados->count() }} imputados activos</p>
                                </div>
                                <div class="divide-y divide-slate-100">
                                    @forelse ($causaAusente->imputados as $imputadoAusente)
                                        <div wire:key="ausente-imputado-{{ $imputadoAusente->id }}" class="p-4 text-sm">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="break-words font-medium text-slate-900">{{ $imputadoAusente->nombre }}</p>
                                                <span class="font-mono text-xs text-slate-500">{{ $imputadoAusente->rut }}</span>
                                            </div>
                                            <p class="mt-1 break-words text-xs text-slate-600">{{ $imputadoAusente->penal }} · {{ $imputadoAusente->delito }}</p>
                                            @can('visita_carcel.gestionar')
                                                <div class="mt-3 flex justify-end">
                                                    <button type="button" wire:click="eliminarImputado({{ $imputadoAusente->id }})"
                                                        wire:confirm="Se eliminará únicamente a {{ $imputadoAusente->nombre }} (RUT {{ $imputadoAusente->rut }}) de esta causa. La causa y los demás imputados se conservarán. ¿Continuar?"
                                                        wire:loading.attr="disabled" wire:target="eliminarImputado,eliminarCausaAusente"
                                                        class="rounded-lg border border-rose-200 bg-white px-3 py-2 text-xs font-semibold text-rose-700 hover:bg-rose-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-700 disabled:opacity-60">Eliminar imputado</button>
                                                </div>
                                            @endcan
                                        </div>
                                    @empty
                                        <p class="p-4 text-sm text-slate-500">Sin imputados activos asociados.</p>
                                    @endforelse
                                </div>
                                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-100 p-4">
                                    <button type="button" wire:click="verFicha({{ $causaAusente->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">Ver ficha</button>
                                    @can('visita_carcel.gestionar')
                                        <button type="button" wire:click="eliminarCausaAusente({{ $causaAusente->id }})"
                                            wire:confirm="Se eliminará la causa RUC {{ $causaAusente->ruc }} y sus {{ $causaAusente->imputados->count() }} imputados activos. Podrás restaurarlos desde Reportes de control. ¿Continuar?"
                                            wire:loading.attr="disabled" wire:target="eliminarCausaAusente"
                                            class="rounded-lg bg-rose-700 px-3 py-2 text-xs font-semibold text-white hover:bg-rose-800 disabled:opacity-60">Eliminar causa</button>
                                    @endcan
                                </div>
                            </article>
                        @empty
                            <p class="rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">No hay causas activas ausentes por revisar en esta comparación.</p>
                        @endforelse
                        @if ($causasAusentes->hasPages())
                            <div class="pt-2">{{ $causasAusentes->links() }}</div>
                        @endif
                    </div>
                    <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-7">
                        <p class="mr-auto max-w-sm text-xs leading-5 text-slate-500">Las bajas pueden restaurarse desde Reportes de control.</p>
                        <button type="button" wire:click="cerrarRevisionCausasAusentes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cerrar revisión</button>
                    </footer>
                </section>
            </div>
            @endteleport
        @endif

        @if ($informacionImportacionAbierta)
            @teleport('body')
            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="formato-importacion-title"><section class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#0A2E5D]">Comparación de causas</p><h3 id="formato-importacion-title" class="mt-1 text-xl font-semibold text-slate-900">Formato requerido para importar</h3></div><button type="button" wire:click="cerrarInformacionImportacion" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header><div class="space-y-5 p-5 sm:p-7"><div class="grid gap-3 text-sm text-slate-700 sm:grid-cols-2"><p><strong>Archivo:</strong> XLSX o XLS, máximo 20 MB.</p><p><strong>Hoja:</strong> solo se utiliza la primera hoja.</p><p><strong>Cabecera:</strong> debe estar en la primera fila.</p><p><strong>Límite:</strong> 5.000 filas y 20 columnas.</p></div><section class="rounded-xl border border-sky-100 bg-sky-50/50 p-4"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-[#0A2E5D]">Columnas obligatorias</p><table class="mt-3 w-full border-collapse text-left text-sm"><thead><tr class="bg-white"><th class="border border-sky-100 px-3 py-2">RIT</th><th class="border border-sky-100 px-3 py-2">AÑOCAUSA</th><th class="border border-sky-100 px-3 py-2">RUC</th></tr></thead><tbody><tr><td class="border border-sky-100 px-3 py-2 font-mono">142</td><td class="border border-sky-100 px-3 py-2 font-mono">2026</td><td class="border border-sky-100 px-3 py-2 font-mono">2301234567-8</td></tr></tbody></table><p class="mt-3 text-sm text-slate-700">El sistema compara el valor <strong>RIT-AÑOCAUSA</strong>, por ejemplo <strong>142-2026</strong>, con el RUC existente en el catálogo. No crea ni modifica causas o imputados.</p></section><section><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Errores frecuentes</p><ul class="mt-2 space-y-1 text-sm text-slate-700"><li>La cabecera no está en la primera fila.</li><li>Falta una columna obligatoria.</li><li>El RUC no existe o el RIT no coincide con el catálogo.</li><li>El archivo está dañado, protegido o excede los límites.</li></ul></section></div><footer class="flex justify-end border-t border-slate-200 px-5 py-4 sm:px-7"><button type="button" wire:click="cerrarInformacionImportacion" class="rounded-lg bg-[#0A2E5D] px-4 py-2 text-sm font-semibold text-white">Entendido</button></footer></section></div>
            @endteleport
        @endif

        @if ($revisionCausasPendientesAbierta)
            @teleport('body')
            <div class="fixed inset-0 z-[60] bg-slate-950/40" role="dialog" aria-modal="true" aria-labelledby="causas-pendientes-title"><section class="absolute inset-y-0 right-0 flex w-full max-w-3xl flex-col overflow-hidden bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-amber-700">Ingreso pendiente</p><h3 id="causas-pendientes-title" class="mt-1 text-xl font-semibold text-slate-900">Causas e imputados a agregar</h3><p class="mt-1 text-sm text-slate-600">Revise la selección antes de ingresar al catálogo.</p></div><button type="button" wire:click="cerrarRevisionCausasPendientes" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header><div class="flex-1 space-y-3 overflow-y-auto p-5 sm:p-7">@foreach ($causasPendientes as $indiceCausa => $causa)<article wire:key="causa-pendiente-{{ $causa['ruc'] }}" class="rounded-xl border border-slate-200"><label class="flex cursor-pointer items-start gap-3 border-b border-slate-100 p-4"><input type="checkbox" wire:model="causasPendientesSeleccionadas" value="{{ $causa['ruc'] }}" class="mt-1 rounded border-slate-300 text-amber-700 focus:ring-amber-700"><span><strong class="block text-slate-900">RUC {{ $causa['ruc'] }} · RIT {{ $causa['rit'] }}</strong><span class="mt-1 block text-xs text-slate-500">{{ implode(', ', $causa['hojas']) }}</span></span></label><div class="divide-y divide-slate-100">@foreach ($causa['imputados'] as $indiceImputado => $imputado)<div class="p-4 text-sm"><div class="flex flex-wrap items-center justify-between gap-2"><p class="font-medium text-slate-900">{{ $imputado['nombre'] ?: 'Sin nombre' }}</p><span class="font-mono text-xs text-slate-500">{{ $imputado['rut'] ?: 'Sin RUT' }}</span></div><p class="mt-1 text-xs text-slate-600">{{ $imputado['penal'] ?: 'Penal no identificado' }} · {{ $imputado['delito'] ?: 'Sin delito' }}</p>
@include('livewire.partials.visita-carcel-penal-pendiente', ['tipo' => 'causa', 'indice' => $indiceCausa, 'subindice' => $indiceImputado])
@if (! $imputado['valido'])<p class="mt-2 text-xs font-semibold text-red-700">No se ingresará: {{ $imputado['motivo'] }}</p>@endif</div>@endforeach</div></article>@endforeach</div><footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-7">@error('causasPendientesSeleccionadas')<p class="mr-auto self-center text-sm text-red-600">{{ $message }}</p>@enderror<button type="button" wire:click="cerrarRevisionCausasPendientes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button type="button" wire:click="crearCausasPendientes" wire:confirm="Se crearán las causas seleccionadas y sus imputados válidos. ¿Desea continuar?" class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Ingresar seleccionadas</button></footer></section></div>
            @endteleport
        @endif

        @if (false)
            @teleport('body')
            <div class="fixed inset-0 z-[60] bg-slate-950/40" role="dialog" aria-modal="true" aria-labelledby="pendientes-ingreso-title"><section class="absolute inset-y-0 right-0 flex w-full max-w-3xl flex-col overflow-hidden bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-amber-700">Ingreso pendiente</p><h3 id="pendientes-ingreso-title" class="mt-1 text-xl font-semibold text-slate-900">Pendientes de ingreso</h3><p class="mt-1 text-sm text-slate-600">Revise la selección antes de ingresar al catálogo.</p></div><button type="button" wire:click="cerrarRevisionCausasPendientes" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header><div class="flex-1 space-y-6 overflow-y-auto p-5 sm:p-7">@if ($causasPendientes !== [])<section><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-amber-700">Causas no ingresadas</p><div class="mt-3 space-y-3">@foreach ($causasPendientes as $causa)<article wire:key="causa-pendiente-mixta-{{ $causa['ruc'] }}" class="rounded-xl border border-slate-200"><label class="flex cursor-pointer items-start gap-3 border-b border-slate-100 p-4"><input type="checkbox" wire:model="causasPendientesSeleccionadas" value="{{ $causa['ruc'] }}" class="mt-1 rounded border-slate-300 text-amber-700 focus:ring-amber-700"><span><strong class="block text-slate-900">RUC {{ $causa['ruc'] }} · RIT {{ $causa['rit'] }}</strong><span class="mt-1 block text-xs text-slate-500">{{ implode(', ', $causa['hojas']) }}</span></span></label><div class="divide-y divide-slate-100">@foreach ($causa['imputados'] as $imputado)<div class="p-4 text-sm"><p class="font-medium text-slate-900">{{ $imputado['nombre'] ?: 'Sin nombre' }} <span class="font-mono text-xs text-slate-500">{{ $imputado['rut'] ?: 'Sin RUT' }}</span></p><p class="mt-1 text-xs text-slate-600">{{ $imputado['penal'] ?: 'Penal no identificado' }} · {{ $imputado['delito'] ?: 'Sin delito' }}</p>@if (! $imputado['valido'])<p class="mt-2 text-xs font-semibold text-red-700">No se ingresará: {{ $imputado['motivo'] }}</p>@endif</div>@endforeach</div></article>@endforeach</div></section>@endif<section><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-sky-700">Imputados pendientes en causas existentes</p><div class="mt-3 space-y-3">@foreach ($imputadosPendientes as $imputado)<article wire:key="imputado-pendiente-{{ $imputado['id'] }}" class="rounded-xl border border-slate-200"><label class="flex cursor-pointer items-start gap-3 p-4"><input type="checkbox" wire:model="imputadosPendientesSeleccionados" value="{{ $imputado['id'] }}" @disabled(! $imputado['valido']) class="mt-1 rounded border-slate-300 text-sky-700 focus:ring-sky-700"><span class="min-w-0 flex-1"><strong class="block text-slate-900">{{ $imputado['nombre'] ?: 'Sin nombre' }} <span class="font-mono text-xs font-normal text-slate-500">{{ $imputado['rut'] ?: 'Sin RUT' }}</span></strong><span class="mt-1 block text-xs text-slate-600">RUC {{ $imputado['ruc'] }} · RIT {{ $imputado['rit'] }} · {{ $imputado['penal'] ?: 'Penal no identificado' }}</span><span class="mt-1 block text-xs text-slate-500">{{ $imputado['delito'] ?: 'Sin delito' }} · {{ $imputado['hoja'] }}, fila {{ $imputado['fila'] }}</span>@if (! $imputado['valido'])<span class="mt-2 block text-xs font-semibold text-red-700">No se ingresará: {{ $imputado['motivo'] }}</span>@endif</span></label></article>@endforeach</div></section></div><footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-7">@error('causasPendientesSeleccionadas')<p class="mr-auto self-center text-sm text-red-600">{{ $message }}</p>@enderror<button type="button" wire:click="cerrarRevisionCausasPendientes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button type="button" wire:click="crearCausasPendientes" wire:confirm="Se crearán las causas e imputados seleccionados. ¿Desea continuar?" class="rounded-lg bg-amber-700 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-800">Ingresar seleccionadas</button></footer></section></div>
            @endteleport
        @endif

        @if ($revisionImputadosPendientesAbierta)
            @teleport('body')
            <div class="fixed inset-0 z-[60] bg-slate-950/40" role="dialog" aria-modal="true" aria-labelledby="imputados-pendientes-title"><section class="absolute inset-y-0 right-0 flex w-full max-w-3xl flex-col overflow-hidden bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-sky-700">Ingreso pendiente</p><h3 id="imputados-pendientes-title" class="mt-1 text-xl font-semibold text-slate-900">Imputados pendientes en causas existentes</h3><p class="mt-1 text-sm text-slate-600">Seleccione solo los imputados que desea agregar a causas ya registradas.</p></div><button type="button" wire:click="cerrarRevisionCausasPendientes" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header><div class="flex-1 space-y-3 overflow-y-auto p-5 sm:p-7">@foreach ($imputadosPendientes as $indiceImputado => $imputado)<article wire:key="imputado-pendiente-separado-{{ $imputado['id'] }}" class="rounded-xl border border-slate-200"><label class="flex cursor-pointer items-start gap-3 p-4"><input type="checkbox" wire:model="imputadosPendientesSeleccionados" value="{{ $imputado['id'] }}" @disabled(! $imputado['valido']) class="mt-1 rounded border-slate-300 text-sky-700 focus:ring-sky-700"><span class="min-w-0 flex-1"><strong class="block text-slate-900">{{ $imputado['nombre'] ?: 'Sin nombre' }} <span class="font-mono text-xs font-normal text-slate-500">{{ $imputado['rut'] ?: 'Sin RUT' }}</span></strong><span class="mt-1 block text-xs text-slate-600">RUC {{ $imputado['ruc'] }} · RIT {{ $imputado['rit'] }} · {{ $imputado['penal'] ?: 'Penal no identificado' }}</span><span class="mt-1 block text-xs text-slate-500">{{ $imputado['delito'] ?: 'Sin delito' }} · {{ $imputado['hoja'] }}, fila {{ $imputado['fila'] }}</span>@if (! $imputado['valido'])<span class="mt-2 block text-xs font-semibold text-red-700">No se ingresará: {{ $imputado['motivo'] }}</span>@endif</span></label>
<div class="px-4 pb-4">@include('livewire.partials.visita-carcel-penal-pendiente', ['tipo' => 'imputado', 'indice' => $indiceImputado, 'subindice' => 0])</div>
</article>@endforeach</div><footer class="flex flex-wrap justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-7">@error('imputadosPendientesSeleccionados')<p class="mr-auto self-center text-sm text-red-600">{{ $message }}</p>@enderror<button type="button" wire:click="cerrarRevisionCausasPendientes" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button type="button" wire:click="crearImputadosPendientes" wire:confirm="Se crearán los imputados seleccionados en sus causas existentes. ¿Desea continuar?" class="rounded-lg bg-[#0A2E5D] px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Ingresar imputados seleccionados</button></footer></section></div>
            @endteleport
        @endif

        @if ($formularioCausaAbierto)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between border-l-4 border-rose-700 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-rose-700">Ingreso al catalogo</p><h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Registrar nueva causa</h3></div><button type="button" wire:click="cerrarFormularioCausa" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar formulario"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></div>
                <form wire:submit="guardarCausa" class="grid gap-5 border-t border-slate-100 bg-slate-50 p-5 sm:p-7 md:grid-cols-2"><div><label for="ruc" class="mb-1.5 block text-sm font-medium text-slate-700">RUC</label><input id="ruc" type="text" wire:model="ruc" placeholder="Ej. 2301234567-8" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-rose-700 focus:ring-rose-700">@error('ruc') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label for="rit" class="mb-1.5 block text-sm font-medium text-slate-700">RIT</label><input id="rit" type="text" wire:model="rit" placeholder="Ej. 142-2026" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2.5 text-sm focus:border-rose-700 focus:ring-rose-700">@error('rit') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div class="flex justify-end md:col-span-2"><button type="submit" class="rounded-lg bg-[#0A2E5D] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700" wire:loading.attr="disabled" wire:target="guardarCausa">Registrar causa</button></div></form>
            </section>
        @endif

            <div class="hidden overflow-x-auto overflow-y-auto md:block lg:min-h-0 lg:flex-1">
                <table class="min-w-full text-sm"><thead class="sticky top-0 z-10 bg-slate-50 text-left"><tr><th class="px-6 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">RUC</th><th class="px-6 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Imputados</th><th class="px-6 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Acciones</th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($causas as $causa)<tr wire:key="causa-{{ $causa->id }}" class="hover:bg-slate-50/70"><td class="px-6 py-4"><p class="font-semibold text-slate-900">{{ $causa->ruc }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">RIT {{ $causa->rit }}</p></td><td class="px-6 py-4"><span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ $causa->imputados_count }} {{ $causa->imputados_count === 1 ? 'imputado' : 'imputados' }}</span></td><td class="px-6 py-4 text-right"><button type="button" wire:click="verFicha({{ $causa->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:border-rose-300 hover:bg-rose-50 hover:text-rose-800">Ver ficha</button>@can('visita_carcel.gestionar')<button type="button" wire:click="eliminarCausa({{ $causa->id }})" wire:confirm="Se eliminara la causa y sus imputados del catalogo. Continuar?" class="ml-2 inline-flex size-8 items-center justify-center rounded-lg border border-red-100 text-red-600 transition hover:bg-red-600 hover:text-white" title="Eliminar causa" aria-label="Eliminar causa"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>@endcan</td></tr>@empty<tr><td colspan="3" class="px-6 py-12 text-center text-sm text-slate-500">No hay causas activas para mostrar.</td></tr>@endforelse</tbody></table>
            </div>

            <div class="divide-y divide-slate-100 md:hidden">@forelse ($causas as $causa)<article wire:key="causa-mobile-{{ $causa->id }}" class="space-y-3 p-5"><div><p class="font-semibold text-slate-900">{{ $causa->ruc }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">RIT {{ $causa->rit }}</p></div><span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">{{ $causa->imputados_count }} {{ $causa->imputados_count === 1 ? 'imputado' : 'imputados' }}</span><div><button type="button" wire:click="verFicha({{ $causa->id }})" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Ver ficha</button>@can('visita_carcel.gestionar')<button type="button" wire:click="eliminarCausa({{ $causa->id }})" wire:confirm="Se eliminara la causa y sus imputados del catalogo. Continuar?" class="ml-2 rounded-lg border border-red-100 px-3 py-2 text-xs font-semibold text-red-600">Eliminar causa</button>@endcan</div></article>@empty<p class="px-6 py-12 text-center text-sm text-slate-500">No hay causas activas para mostrar.</p>@endforelse</div>
            <div class="shrink-0 border-t border-slate-200 px-5 py-4 sm:px-7">
                {{ $causas->links() }}
            </div>
        </section>
        @endif

        @if ($causaSeleccionada)
            @teleport('body')
            <div class="fixed inset-0 z-50 bg-slate-950/40" role="dialog" aria-modal="true" aria-labelledby="ficha-causa-title"><section class="absolute inset-y-0 right-0 flex w-full max-w-3xl flex-col overflow-hidden bg-white shadow-2xl"><header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-rose-700">Ficha de causa</p><h3 id="ficha-causa-title" class="mt-1 text-xl font-semibold text-slate-900">{{ $causaSeleccionada->ruc }}</h3><p class="mt-1 font-mono text-xs text-slate-500">RIT {{ $causaSeleccionada->rit }}</p></div><button type="button" wire:click="cerrarFicha" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar ficha"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header><div class="flex-1 overflow-y-auto p-5 sm:p-7"><div class="flex items-center justify-between gap-3"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Imputados activos</p>@can('visita_carcel.gestionar')<button type="button" wire:click="abrirFormularioImputado" class="rounded-lg bg-[#0A2E5D] px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700"><i class="fa-solid fa-plus mr-1" aria-hidden="true"></i> Agregar imputado</button>@endcan</div>
                @if ($formularioImputadoAbierto)<form wire:submit="guardarImputado" class="mt-4 grid gap-4 rounded-xl border border-rose-100 bg-rose-50/40 p-4 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Nombre imputado</label><input type="text" wire:model="nombre" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">RUT</label><input type="text" wire:model="rut" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('rut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Fecha ingreso PP</label><input type="date" wire:model="fechaIngresoPp" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('fechaIngresoPp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Penal</label><select wire:model="penal" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm"><option value="">Seleccione un penal</option>@foreach ($penales as $penalActivo)<option value="{{ $penalActivo->nombre }}">{{ $penalActivo->nombre }}</option>@endforeach</select>@error('penal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Delito</label><input type="text" wire:model="delito" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('delito') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div class="flex justify-end gap-2 sm:col-span-2"><button type="button" wire:click="cerrarFormularioImputado" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Cancelar</button><button type="submit" class="rounded-lg bg-[#0A2E5D] px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">Guardar imputado</button></div></form>@endif
                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Imputado</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Penal y delito</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Ingreso PP</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($causaSeleccionada->imputados as $imputado)<tr wire:key="imputado-{{ $imputado->id }}"><td class="px-4 py-4"><p class="font-medium text-slate-900">{{ $imputado->nombre }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $imputado->rut }}</p></td><td class="px-4 py-4"><p class="text-slate-800">{{ $imputado->penal }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $imputado->delito }}</p></td><td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-600">{{ $imputado->fecha_ingreso_pp->format('d/m/Y') }}</td><td class="px-4 py-4 text-right">@can('visita_carcel.gestionar')<button type="button" wire:click="abrirEdicionImputado({{ $imputado->id }})" class="mr-2 inline-flex size-8 items-center justify-center rounded-lg border border-sky-100 text-[#0A2E5D] hover:bg-sky-50" title="Editar imputado" aria-label="Editar imputado"><i class="fa-solid fa-pen" aria-hidden="true"></i></button><button type="button" wire:click="eliminarImputado({{ $imputado->id }})" wire:confirm="Se eliminara este imputado del catalogo. Continuar?" class="inline-flex size-8 items-center justify-center rounded-lg border border-red-100 text-red-600 hover:bg-red-600 hover:text-white" title="Eliminar imputado" aria-label="Eliminar imputado"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>@endcan</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No hay imputados activos en esta causa.</td></tr>@endforelse</tbody></table></div><section class="mt-6 border-t border-slate-200 pt-5"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Movimientos de causa</p><div class="mt-3 space-y-3">@forelse ($causaSeleccionada->movimientos as $movimiento)<article wire:key="movimiento-{{ $movimiento->id }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $movimiento->evento }}</p><p class="mt-1 text-xs text-slate-600">{{ $movimiento->tipo_tramite }} · {{ $movimiento->estado_evento }}</p></div><time class="whitespace-nowrap font-mono text-xs text-rose-700">{{ $movimiento->fecha_evento->format('d/m/Y') }}</time></div><p class="mt-2 text-xs text-slate-500">Importado desde {{ $movimiento->importacion->archivo }} el {{ $movimiento->created_at->format('d/m/Y H:i') }}</p></article>@empty<p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">Esta causa aun no registra movimientos importados.</p>@endforelse</div></section></div></section></div>
            @endteleport

        @endif

        @if ($imputadoRevisionPpId)
            @teleport('body')
            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="revision-pp-title"><form wire:submit="actualizarRevisionPp" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl"><div class="flex items-start justify-between gap-4"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-amber-700">Ficha de causa</p><h3 id="revision-pp-title" class="mt-1 text-lg font-semibold text-slate-900">Actualizar última revisión PP</h3></div><button type="button" wire:click="cerrarFormularioRevisionPp" class="text-slate-500 hover:text-slate-900" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></div><p class="mt-2 text-sm text-slate-600">La nueva fecha retira al imputado de esta alerta cuando queda bajo los 160 días.</p><label for="ultima-revision-pp" class="mt-5 block text-sm font-medium text-slate-700">Fecha última revisión de PP</label><div class="mt-1 flex gap-2"><input id="ultima-revision-pp" type="date" wire:model="ultimaRevisionPp" class="min-w-0 flex-1 rounded-lg border-slate-300 px-3 py-2.5 text-sm focus:border-amber-700 focus:ring-amber-700"><button type="button" wire:click="usarFechaActualRevisionPp" class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100">Hoy</button></div>@error('ultimaRevisionPp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror<div class="mt-5 flex justify-end gap-2"><button type="button" wire:click="cerrarFormularioRevisionPp" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button type="submit" class="rounded-lg bg-amber-700 px-3 py-2 text-sm font-semibold text-white hover:bg-amber-800">Guardar actualización</button></div></form></div>
            @endteleport
        @endif

        @if ($imputadoEditandoId)
            @teleport('body')
            <div class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-950/40 p-4" role="dialog" aria-modal="true" aria-labelledby="editar-imputado-title">
                <form wire:submit="guardarEdicionImputado" class="flex max-h-[calc(100dvh-2rem)] w-full max-w-4xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                    <header class="flex items-start justify-between border-b border-slate-200 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-[#0A2E5D]">Ficha de causa</p><h3 id="editar-imputado-title" class="mt-1 text-xl font-semibold text-slate-900">Editar imputado</h3><p class="mt-1 text-sm text-slate-600">Actualiza los antecedentes del imputado y conserva la trazabilidad de cada cambio.</p></div><button type="button" wire:click="cerrarEdicionImputado" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></header>
                    <div class="flex-1 overflow-y-auto p-5 sm:p-7">
                        <section><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Identificación</p><div class="mt-3 grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium text-slate-700">Nombre imputado</label><input type="text" wire:model="imputadoEdicion.nombre" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm">@error('imputadoEdicion.nombre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">RUT</label><input type="text" wire:model="imputadoEdicion.rut" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm">@error('imputadoEdicion.rut')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Penal</label><select wire:model="imputadoEdicion.penal" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@if (! $penales->contains('nombre', $imputadoEdicion['penal'] ?? ''))<option value="{{ $imputadoEdicion['penal'] ?? '' }}">Penal inactivo: {{ $imputadoEdicion['penal'] ?? '' }}</option>@endif@foreach ($penales as $penalActivo)<option value="{{ $penalActivo->nombre }}">{{ $penalActivo->nombre }}</option>@endforeach</select>@error('imputadoEdicion.penal')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Delito</label><textarea wire:model="imputadoEdicion.delito" rows="2" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm"></textarea>@error('imputadoEdicion.delito')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div></section>
                        <section class="mt-6 rounded-xl border border-amber-100 bg-amber-50/50 p-4"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-amber-800">Situación de prisión preventiva</p><div class="mt-3 grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium text-slate-700">Fecha ingreso PP</label><input type="date" wire:model="imputadoEdicion.fecha_ingreso_pp" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('imputadoEdicion.fecha_ingreso_pp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Plazo cierre</label><input type="date" wire:model="imputadoEdicion.plazo_cierre" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('imputadoEdicion.plazo_cierre')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Última revisión PP</label><input type="date" wire:model="imputadoEdicion.ultima_rev_pp" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('imputadoEdicion.ultima_rev_pp')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Fecha última resolución</label><input type="date" wire:model="imputadoEdicion.fecha_ult_res" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('imputadoEdicion.fecha_ult_res')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div></section>
                        <section class="mt-6"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Intervinientes y resolución</p><div class="mt-3 grid gap-4 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium text-slate-700">Juez</label><input type="text" wire:model="imputadoEdicion.juez" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm">@error('imputadoEdicion.juez')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Defensor</label><input type="text" wire:model="imputadoEdicion.defensor" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm">@error('imputadoEdicion.defensor')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div><div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Última resolución</label><textarea wire:model="imputadoEdicion.ult_resolucion" rows="3" class="w-full rounded-lg border-slate-300 px-3 py-2 text-sm"></textarea>@error('imputadoEdicion.ult_resolucion')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror</div></div></section>
                        @if ($historialEdicion !== [])<details class="mt-6 border-t border-slate-200 pt-4"><summary class="cursor-pointer font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Últimos cambios registrados</summary><div class="mt-3 space-y-2">@foreach ($historialEdicion as $entrada)<article class="rounded-lg bg-slate-50 p-3 text-xs text-slate-600"><p class="font-medium text-slate-800">{{ $entrada['fecha'] }} · Usuario #{{ $entrada['usuario_id'] ?? 'sistema' }}</p><p class="mt-1">{{ collect($entrada['cambios'])->keys()->implode(', ') }}</p></article>@endforeach</div></details>@endif
                    </div>
                    <footer class="flex justify-end gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:px-7"><button type="button" wire:click="cerrarEdicionImputado" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</button><button type="submit" class="rounded-lg bg-[#0A2E5D] px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Guardar cambios</button></footer>
                </form>
            </div>
            @endteleport
        @endif
    </div>
</div>

@script
<script>
    if (! window.visitaCarcelDrawerClosing) {
        window.visitaCarcelDrawerClosing = true;

        const drawerIds = ['gestion-penales-title', 'causas-pendientes-title', 'imputados-pendientes-title', 'causas-ausentes-title', 'ficha-causa-title'];
        const drawers = () => drawerIds.map((id) => document.querySelector(`[aria-labelledby="${id}"]`)).filter(Boolean);
        const close = (drawer) => drawer?.querySelector('button[aria-label^="Cerrar"]')?.click();

        document.addEventListener('click', (event) => close(drawers().find((drawer) => event.target === drawer)));
        document.addEventListener('keydown', (event) => event.key === 'Escape' && close(drawers().find((drawer) => drawer.getClientRects().length)));
    }
</script>
@endscript
