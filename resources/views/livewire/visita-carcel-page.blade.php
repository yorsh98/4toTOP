<x-slot:header>
    <x-page-heading eyebrow="Visita Carcel · Catalogo" title="Visita Carcel" />
</x-slot:header>

<div class="py-2 lg:h-[calc(100dvh-4rem)]">
    <div class="mx-auto max-w-7xl space-y-5 px-4 sm:px-6 lg:flex lg:h-full lg:flex-col lg:space-y-3 lg:px-8">
        <x-flash-notifications />

        <div class="flex shrink-0 items-center justify-between rounded-2xl border border-slate-200 bg-white p-2 shadow-sm">
            <button type="button" wire:click="seleccionarModo('comparacion')" @class(['flex-1 rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-[#0A2E5D] text-white shadow-sm' => $modo === 'comparacion', 'text-slate-600 hover:bg-slate-50' => $modo !== 'comparacion'])>Comparacion</button>
            <button type="button" wire:click="seleccionarModo('alimentacion')" @class(['flex-1 rounded-xl px-4 py-3 text-sm font-semibold transition', 'bg-rose-700 text-white shadow-sm' => $modo === 'alimentacion', 'text-slate-600 hover:bg-slate-50' => $modo !== 'alimentacion'])>Alimentacion</button>
        </div>

        @if ($modo === 'alimentacion')
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
                <div class="shrink-0 border-l-4 border-rose-700 px-6 py-5">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-rose-700">Actualizacion de planillas</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-900">Reimportar informacion por penal</h3>
                    <p class="mt-1 text-sm text-slate-600">Selecciona varias planillas XLS/XLSX o un ZIP. Sobrescribe los seis campos de seguimiento y conserva un historial por imputado.</p>
                </div>

                <form wire:submit="actualizarDesdeArchivos" class="shrink-0 flex flex-col gap-4 border-t border-slate-100 bg-slate-50 p-6 sm:flex-row sm:items-end">
                    <div class="flex-1">
                        <label class="mb-1 block text-sm font-medium text-slate-700">Planillas completadas</label>
                        <input type="file" wire:model="archivosActualizacion" accept=".xlsx,.xls,.zip" multiple class="block w-full rounded-lg border border-slate-300 bg-white text-sm">
                        @error('archivosActualizacion') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('archivosActualizacion.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-rose-700 px-4 py-2.5 text-sm font-semibold text-white" wire:loading.attr="disabled" wire:target="actualizarDesdeArchivos,archivosActualizacion">Actualizar catalogo</button>
                </form>

                <div class="shrink-0 flex justify-end border-t border-slate-100 px-6 py-4">
                    <a href="{{ route('visita-carcel.exportar-penales') }}" class="rounded-lg bg-[#0A2E5D] px-4 py-2.5 text-sm font-semibold text-white">Exportar imputados activos por penal</a>
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
        @else
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:flex lg:min-h-0 lg:flex-1 lg:flex-col">
            <div class="shrink-0 flex flex-col gap-4 border-b border-slate-100 px-5 py-5 sm:px-7 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-rose-700">Registro activo</p>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Comparacion de causas</h3>
                </div>
                <div class="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap sm:justify-end lg:w-auto">
                    <div class="relative flex-1 lg:w-80">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400" aria-hidden="true"></i>
                        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar RUC, RIT o imputado" class="w-full rounded-lg border-slate-300 py-2.5 pl-9 pr-3 text-sm focus:border-rose-700 focus:ring-rose-700">
                    </div>
                    <span class="inline-flex items-center justify-center rounded-full bg-rose-50 px-3 py-2.5 text-xs font-semibold text-rose-700">Total: {{ $causas->total() }}</span>
                    @can('visita_carcel.gestionar')
                        @if ($coincidenciasCruce !== [])<a href="{{ route('visita-carcel.exportar-por-penal') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-800 transition hover:bg-emerald-100"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Exportar comparacion</a><button type="button" wire:click="salirModoCruce" class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700">Salir de comparacion</button>@endif
                        <button type="button" wire:click="abrirFormularioImportacion" class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#0A2E5D] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700"><i class="fa-solid fa-file-arrow-up" aria-hidden="true"></i> Importar para comparar</button>
                    @endcan
                </div>
            </div>

        @if ($formularioImportacionAbierto)
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-start justify-between border-l-4 border-sky-700 px-5 py-5 sm:px-7"><div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-sky-700">Comparacion temporal</p><h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Importar archivo del sistema externo</h3><p class="mt-1 text-sm text-slate-600">El archivo requiere RIT, AÑOCAUSA y RUC. No modifica el catalogo.</p></div><button type="button" wire:click="cerrarFormularioImportacion" class="inline-flex size-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100" aria-label="Cerrar formulario"><i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i></button></div>
                <div class="border-t border-slate-100 bg-slate-50 p-5 sm:p-7">
                    <form wire:submit="importarMovimientos" class="flex flex-col gap-4 sm:flex-row sm:items-end"><div class="flex-1"><label for="archivo-movimientos" class="mb-1.5 block text-sm font-medium text-slate-700">Archivo XLSX o XLS</label><input id="archivo-movimientos" type="file" wire:model="archivoMovimientos" accept=".xlsx,.xls" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-3 file:border-0 file:bg-slate-200 file:px-3 file:py-2.5 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-300">@error('archivoMovimientos') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><button type="submit" class="rounded-lg bg-[#0A2E5D] px-4 py-2.5 text-sm font-semibold text-white hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="importarMovimientos,archivoMovimientos">Importar y contrastar</button></form>
                    @if ($resultadoImportacion !== [])<div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><div class="rounded-lg border border-slate-200 bg-white p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-slate-500">Procesadas</p><p class="mt-1 text-xl font-semibold text-slate-900">{{ $resultadoImportacion['filas_procesadas'] }}</p></div><div class="rounded-lg border border-emerald-100 bg-emerald-50 p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-emerald-700">Movimientos asociados</p><p class="mt-1 text-xl font-semibold text-emerald-800">{{ $resultadoImportacion['movimientos_creados'] }}</p></div><div class="rounded-lg border border-amber-100 bg-amber-50 p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-amber-700">Sin causa o imputado</p><p class="mt-1 text-xl font-semibold text-amber-800">{{ $resultadoImportacion['causas_no_encontradas'] + $resultadoImportacion['imputados_no_encontrados'] }}</p></div><div class="rounded-lg border border-red-100 bg-red-50 p-3"><p class="font-mono text-[10px] font-bold uppercase tracking-wider text-red-700">Inconsistencias o ambiguas</p><p class="mt-1 text-xl font-semibold text-red-800">{{ $resultadoImportacion['inconsistencias'] + $resultadoImportacion['coincidencias_ambiguas'] }}</p></div></div>@endif
                </div>
            </section>
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
                @if ($formularioImputadoAbierto)<form wire:submit="guardarImputado" class="mt-4 grid gap-4 rounded-xl border border-rose-100 bg-rose-50/40 p-4 sm:grid-cols-2"><div class="sm:col-span-2"><label class="mb-1 block text-sm font-medium text-slate-700">Nombre imputado</label><input type="text" wire:model="nombre" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('nombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">RUT</label><input type="text" wire:model="rut" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('rut') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Fecha ingreso PP</label><input type="date" wire:model="fechaIngresoPp" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('fechaIngresoPp') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Penal</label><input type="text" wire:model="penal" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('penal') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div><label class="mb-1 block text-sm font-medium text-slate-700">Delito</label><input type="text" wire:model="delito" class="w-full rounded-lg border-slate-300 bg-white px-3 py-2 text-sm">@error('delito') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror</div><div class="flex justify-end gap-2 sm:col-span-2"><button type="button" wire:click="cerrarFormularioImputado" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700">Cancelar</button><button type="submit" class="rounded-lg bg-[#0A2E5D] px-3 py-2 text-xs font-semibold text-white hover:bg-rose-700">Guardar imputado</button></div></form>@endif
                <div class="mt-4 overflow-x-auto rounded-xl border border-slate-200"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Imputado</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Penal y delito</th><th class="px-4 py-3 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Ingreso PP</th><th class="px-4 py-3"></th></tr></thead><tbody class="divide-y divide-slate-100">@forelse ($causaSeleccionada->imputados as $imputado)<tr wire:key="imputado-{{ $imputado->id }}"><td class="px-4 py-4"><p class="font-medium text-slate-900">{{ $imputado->nombre }}</p><p class="mt-0.5 font-mono text-xs text-slate-500">{{ $imputado->rut }}</p></td><td class="px-4 py-4"><p class="text-slate-800">{{ $imputado->penal }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $imputado->delito }}</p></td><td class="whitespace-nowrap px-4 py-4 font-mono text-xs text-slate-600">{{ $imputado->fecha_ingreso_pp->format('d/m/Y') }}</td><td class="px-4 py-4 text-right">@can('visita_carcel.gestionar')<button type="button" wire:click="eliminarImputado({{ $imputado->id }})" wire:confirm="Se eliminara este imputado del catalogo. Continuar?" class="inline-flex size-8 items-center justify-center rounded-lg border border-red-100 text-red-600 hover:bg-red-600 hover:text-white" title="Eliminar imputado" aria-label="Eliminar imputado"><i class="fa-solid fa-trash" aria-hidden="true"></i></button>@endcan</td></tr>@empty<tr><td colspan="4" class="px-4 py-8 text-center text-sm text-slate-500">No hay imputados activos en esta causa.</td></tr>@endforelse</tbody></table></div><section class="mt-6 border-t border-slate-200 pt-5"><p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Movimientos de causa</p><div class="mt-3 space-y-3">@forelse ($causaSeleccionada->movimientos as $movimiento)<article wire:key="movimiento-{{ $movimiento->id }}" class="rounded-xl border border-slate-200 bg-slate-50 p-4"><div class="flex items-start justify-between gap-3"><div><p class="font-semibold text-slate-900">{{ $movimiento->evento }}</p><p class="mt-1 text-xs text-slate-600">{{ $movimiento->tipo_tramite }} · {{ $movimiento->estado_evento }}</p></div><time class="whitespace-nowrap font-mono text-xs text-rose-700">{{ $movimiento->fecha_evento->format('d/m/Y') }}</time></div><p class="mt-2 text-xs text-slate-500">Importado desde {{ $movimiento->importacion->archivo }} el {{ $movimiento->created_at->format('d/m/Y H:i') }}</p></article>@empty<p class="rounded-xl border border-dashed border-slate-300 px-4 py-6 text-center text-sm text-slate-500">Esta causa aun no registra movimientos importados.</p>@endforelse</div></section></div></section></div>
            @endteleport
        @endif
    </div>
</div>
