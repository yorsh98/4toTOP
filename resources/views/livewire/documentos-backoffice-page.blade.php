<x-slot:header>
    <x-page-heading eyebrow="Documentos · Backoffice" title="Biblioteca documental">
        <x-slot:actions>
            <span class="hidden rounded-full border border-slate-200 bg-slate-50 px-3 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 sm:inline-flex">
            {{ count($procedimientos) + count($documentos) }} registros
            </span>
        </x-slot:actions>
    </x-page-heading>
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-notifications />

        @if ($errors->any())
            <div class="rounded-md bg-red-100 px-4 py-2 text-sm text-red-800">
                Revisa los campos del formulario e intenta nuevamente.
            </div>
        @endif

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <button type="button" wire:click="toggleSection('procedimientos')" class="flex w-full cursor-pointer items-center justify-between border-l-4 border-sky-700 px-5 py-5 text-left text-slate-900 transition hover:bg-sky-50/50 sm:px-7">
                <span>
                    <span class="block text-xs font-bold uppercase tracking-[0.18em] text-sky-700">Coleccion A</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Procedimientos del tribunal</h3>
                </span>
                <svg class="size-5 text-slate-600 transition-transform {{ ($sectionsOpen['procedimientos'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['procedimientos'] ?? true)
            <div class="space-y-5 bg-[#fbfcfe] p-4 text-slate-900 sm:p-6">

                @can('documentos.gestionar')
                <form wire:submit="addProcedimiento" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-5">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Nombre del procedimiento</label>
                        <input type="text" wire:model="procedimientoNombre" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-sky-700 focus:ring-sky-700" placeholder="Ej. Control de asistencia">
                        @error('procedimientoNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Orden</label>
                        <input type="number" min="1" wire:model="procedimientoOrden" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm focus:border-sky-700 focus:ring-sky-700">
                        @error('procedimientoOrden') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Archivo</label>
                        <input type="file" wire:model="procedimientoArchivo" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-slate-50 text-xs text-slate-600 file:mr-3 file:border-0 file:bg-slate-200 file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-300">
                        @error('procedimientoArchivo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="w-full whitespace-nowrap rounded-lg bg-[#102b46] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="addProcedimiento,procedimientoArchivo">
                            Incorporar al registro
                        </button>
                    </div>
                    <p class="md:col-span-12 text-xs text-slate-500">PDF, DOC o DOCX · máximo 20 MB</p>
                </form>
                @endcan

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="w-20 px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">N°</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Documento</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Ingreso</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Gestion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($procedimientos as $i => $item)
                                <tr wire:key="proc-{{ $item['id'] }}">
                                    <td class="px-5 py-3.5"><span class="font-mono text-sm font-bold text-slate-400">{{ str_pad((string) $item['orden'], 2, '0', STR_PAD_LEFT) }}</span></td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="group/link inline-flex items-center gap-3 font-medium text-slate-800 transition hover:text-sky-700">
                                            <span class="flex size-8 items-center justify-center rounded-md bg-sky-50 text-sky-700"><i class="far fa-file-alt"></i></span>
                                            <span>{{ $item['nombre'] }}</span>
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5"><span class="font-mono text-xs text-slate-500">{{ $item['created_at'] ?? 'Migrado' }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        @can('documentos.gestionar')<button type="button" wire:click="removeProcedimiento({{ $item['id'] }})" wire:confirm="El procedimiento se archivara y dejara de mostrarse publicamente. Continuar?" class="inline-flex size-8 items-center justify-center rounded-md border border-red-100 text-red-600 transition hover:bg-red-600 hover:text-white" title="Archivar" aria-label="Archivar">
                                            <i class="fas fa-trash"></i>
                                        </button>@endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin procedimientos cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <button type="button" wire:click="toggleSection('documentos')" class="flex w-full cursor-pointer items-center justify-between border-l-4 border-indigo-700 px-5 py-5 text-left text-slate-900 transition hover:bg-indigo-50/50 sm:px-7">
                <span>
                    <span class="block text-xs font-bold uppercase tracking-[0.18em] text-indigo-700">Coleccion B</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Documentos institucionales</h3>
                </span>
                <svg class="size-5 text-slate-600 transition-transform {{ ($sectionsOpen['documentos'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['documentos'] ?? true)
            <div class="space-y-5 bg-[#fbfcfe] p-4 text-slate-900 sm:p-6">

                @can('documentos.gestionar')
                <form wire:submit="addDocumento" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-5">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Nombre del documento</label>
                        <input type="text" wire:model="documentoNombre" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-700 focus:ring-indigo-700" placeholder="Ej. Modelo caja chica">
                        @error('documentoNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Orden</label>
                        <input type="number" min="1" wire:model="documentoOrden" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm focus:border-indigo-700 focus:ring-indigo-700">
                        @error('documentoOrden') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Archivo</label>
                        <input type="file" wire:model="documentoArchivo" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-slate-50 text-xs text-slate-600 file:mr-3 file:border-0 file:bg-slate-200 file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-300">
                        @error('documentoArchivo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="w-full whitespace-nowrap rounded-lg bg-[#243b5a] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="addDocumento,documentoArchivo">
                            Incorporar al registro
                        </button>
                    </div>
                    <p class="md:col-span-12 text-xs text-slate-500">PDF, DOC o DOCX · máximo 20 MB</p>
                </form>
                @endcan

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="w-20 px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">N°</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Documento</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Ingreso</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Gestion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($documentos as $i => $item)
                                <tr wire:key="doc-{{ $item['id'] }}">
                                    <td class="px-5 py-3.5"><span class="font-mono text-sm font-bold text-slate-400">{{ str_pad((string) $item['orden'], 2, '0', STR_PAD_LEFT) }}</span></td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 font-medium text-slate-800 transition hover:text-indigo-700">
                                            <span class="flex size-8 items-center justify-center rounded-md bg-indigo-50 text-indigo-700"><i class="far fa-file-alt"></i></span>
                                            <span>{{ $item['nombre'] }}</span>
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5"><span class="font-mono text-xs text-slate-500">{{ $item['created_at'] ?? 'Migrado' }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        @can('documentos.gestionar')<button type="button" wire:click="removeDocumento({{ $item['id'] }})" wire:confirm="El documento se archivara y dejara de mostrarse publicamente. Continuar?" class="inline-flex size-8 items-center justify-center rounded-md border border-red-100 text-red-600 transition hover:bg-red-600 hover:text-white" title="Archivar" aria-label="Archivar">
                                            <i class="fas fa-trash"></i>
                                        </button>@endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center text-gray-500">Sin documentos cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <button type="button" wire:click="toggleSection('migracion')" class="flex w-full cursor-pointer items-center justify-between border-l-4 border-teal-700 px-5 py-5 text-left text-slate-900 transition hover:bg-teal-50/50 sm:px-7">
                <span>
                    <span class="block text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Registro C</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Migracion Unijud</h3>
                </span>
                <svg class="size-5 text-slate-600 transition-transform {{ ($sectionsOpen['migracion'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['migracion'] ?? true)
            <div class="space-y-5 bg-[#fbfcfe] p-4 text-slate-900 sm:p-6">

                <form wire:submit="addTribunal" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-9">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Nombre del tribunal</label>
                        <input type="text" wire:model="tribunalNombre" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-teal-700 focus:ring-teal-700" placeholder="Ej. Juzgado de Garantia de Santiago">
                        @error('tribunalNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="w-full whitespace-nowrap rounded-lg bg-teal-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-teal-800 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="addTribunal">
                            Incorporar tribunal
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Tribunal migrado</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Gestion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($tribunales as $i => $item)
                                <tr wire:key="trib-{{ $i }}">
                                    <td class="px-5 py-3.5"><span class="inline-flex items-center gap-3 font-medium text-slate-800"><span class="flex size-8 items-center justify-center rounded-md bg-teal-50 text-teal-700"><i class="fas fa-landmark"></i></span>{{ $item['nombre'] }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button type="button" wire:click="removeTribunal({{ $i }})" wire:confirm="El tribunal se quitara del listado. Continuar?" class="inline-flex size-8 items-center justify-center rounded-md border border-red-100 text-red-600 transition hover:bg-red-600 hover:text-white" title="Quitar" aria-label="Quitar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center text-gray-500">Sin tribunales cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <button type="button" wire:click="toggleSection('encargados')" class="flex w-full cursor-pointer items-center justify-between border-l-4 border-violet-700 px-5 py-5 text-left text-slate-900 transition hover:bg-violet-50/50 sm:px-7">
                <span>
                    <span class="block text-xs font-bold uppercase tracking-[0.18em] text-violet-700">Registro D</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">N° Encargado Audiencia</h3>
                </span>
                <svg class="size-5 text-slate-600 transition-transform {{ ($sectionsOpen['encargados'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['encargados'] ?? true)
            <div class="space-y-5 bg-[#fbfcfe] p-4 text-slate-900 sm:p-6">

                <form wire:submit="addEncargadoAudiencia" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-6">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Nombre</label>
                        <input type="text" wire:model="encargadoAudienciaNombre" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-violet-700 focus:ring-violet-700" placeholder="Ej. Leonardo Morales">
                        @error('encargadoAudienciaNombre') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Numeros asignados</label>
                        <input type="text" wire:model="encargadoAudienciaNumeros" class="w-full rounded-lg border-slate-300 bg-slate-50 px-3 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-violet-700 focus:ring-violet-700" placeholder="Ej. 1-6">
                        @error('encargadoAudienciaNumeros') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="w-full whitespace-nowrap rounded-lg bg-violet-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-violet-800 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="addEncargadoAudiencia">
                            Incorporar encargado
                        </button>
                    </div>
                </form>

                <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                    <table class="min-w-full text-sm">
                        <thead class="border-b border-slate-200 bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Encargado</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Rango asignado</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em] text-slate-500">Gestion</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($encargadosAudiencia as $i => $item)
                                <tr wire:key="enc-{{ $i }}">
                                    <td class="px-5 py-3.5"><span class="inline-flex items-center gap-3 font-medium text-slate-800"><span class="flex size-8 items-center justify-center rounded-md bg-violet-50 text-violet-700"><i class="fas fa-user"></i></span>{{ $item['nombre'] }}</span></td>
                                    <td class="px-5 py-3.5"><span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 font-mono text-xs font-semibold text-violet-700">{{ $item['numeros'] }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button type="button" wire:click="removeEncargadoAudiencia({{ $i }})" wire:confirm="El encargado se quitara del listado. Continuar?" class="inline-flex size-8 items-center justify-center rounded-md border border-red-100 text-red-600 transition hover:bg-red-600 hover:text-white" title="Quitar" aria-label="Quitar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-gray-500">Sin encargados cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-l-4 border-amber-600 px-5 py-5 sm:px-7">
                <span class="block text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Registro E</span>
                <h3 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">Buscador de sentencias</h3>
            </div>
            <div class="space-y-5 bg-[#fbfcfe] p-4 text-slate-900 sm:p-6">
                <p class="text-sm text-slate-600">Incorpora un archivo Excel para actualizar el buscador. Las filas ya existentes se omiten automáticamente.</p>

                <form wire:submit="importSentencias" class="grid grid-cols-1 gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-9">
                        <label class="mb-1.5 block font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500">Archivo Excel</label>
                        <input type="file" wire:model="sentenciasExcel" class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-slate-50 text-xs text-slate-600 file:mr-3 file:border-0 file:bg-slate-200 file:px-3 file:py-2.5 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-300" accept=".xlsx">
                        @error('sentenciasExcel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="w-full whitespace-nowrap rounded-lg bg-amber-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-800 disabled:cursor-not-allowed disabled:opacity-60" wire:loading.attr="disabled" wire:target="importSentencias,sentenciasExcel">
                            Importar Excel
                        </button>
                    </div>
                    <p class="md:col-span-12 text-xs text-slate-500">Formato admitido: XLSX · máximo 30 MB</p>
                </form>
            </div>
        </section>
    </div>
</div>
