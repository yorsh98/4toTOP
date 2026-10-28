<x-slot:header>
    <x-page-heading eyebrow="Documentos · Backoffice" title="Biblioteca documental">
        <x-slot:actions>
            <span class="bo-chip hidden font-mono text-xs sm:inline-flex">
            {{ count($procedimientos) + count($documentos) }} registros
            </span>
        </x-slot:actions>
    </x-page-heading>
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-notifications />

        @if ($errors->any())
            <div class="bo-card bo-error px-4 py-2 text-sm">
                Revisa los campos del formulario e intenta nuevamente.
            </div>
        @endif

        <section class="bo-section bo-accent-sky overflow-hidden">
            <button type="button" wire:click="toggleSection('procedimientos')" class="bo-section-toggle flex w-full items-center justify-between px-5 py-5 text-left sm:px-7">
                <span>
                    <span class="bo-eyebrow block">Colección A</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Procedimientos del tribunal</h3>
                </span>
                <svg class="size-5 transition-transform {{ ($sectionsOpen['procedimientos'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['procedimientos'] ?? true)
            <div class="space-y-5 p-4 sm:p-6">

                @can('documentos.gestionar')
                <form wire:submit="addProcedimiento" class="bo-card grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-5">
                        <label class="bo-label mb-1.5">Nombre del procedimiento</label>
                        <input type="text" wire:model="procedimientoNombre" class="bo-input w-full text-sm" placeholder="Ej. Control de asistencia">
                        @error('procedimientoNombre') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label class="bo-label mb-1.5">Orden</label>
                        <input type="number" min="1" wire:model="procedimientoOrden" class="bo-input w-full text-sm">
                        @error('procedimientoOrden') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="bo-label mb-1.5">Archivo</label>
                        <input type="file" wire:model="procedimientoArchivo" class="bo-input w-full text-sm">
                        @error('procedimientoArchivo') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="bo-btn bo-btn--primary w-full whitespace-nowrap" wire:loading.attr="disabled" wire:target="addProcedimiento,procedimientoArchivo">
                            Incorporar al registro
                        </button>
                    </div>
                    <p class="bo-hint md:col-span-12">PDF, DOC o DOCX · máximo 20 MB</p>
                </form>
                @endcan

                <div class="bo-card overflow-x-auto">
                    <table class="bo-table min-w-full text-sm">
                        <thead >
                            <tr>
                                <th class="w-20 px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">N°</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Documento</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Ingreso</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Gestión</th>
                            </tr>
                        </thead>
                        <tbody >
                            @forelse ($procedimientos as $i => $item)
                                <tr wire:key="proc-{{ $item['id'] }}">
                                    <td class="px-5 py-3.5"><span class="font-mono text-sm font-bold">{{ str_pad((string) $item['orden'], 2, '0', STR_PAD_LEFT) }}</span></td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="group/link inline-flex items-center gap-3 font-medium transition">
                                            <span class="flex size-8 items-center justify-center rounded-md"><i class="far fa-file-alt"></i></span>
                                            <span>{{ $item['nombre'] }}</span>
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5"><span class="font-mono text-xs">{{ $item['created_at'] ?? 'Migrado' }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        @can('documentos.gestionar')<button type="button" wire:click="removeProcedimiento({{ $item['id'] }})" wire:confirm="El procedimiento se archivará y dejará de mostrarse públicamente. ¿Continuar?" class="bo-btn-icon" title="Archivar" aria-label="Archivar">
                                            <i class="fas fa-trash"></i>
                                        </button>@endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center">Sin procedimientos cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="bo-section bo-accent-indigo overflow-hidden">
            <button type="button" wire:click="toggleSection('documentos')" class="bo-section-toggle flex w-full items-center justify-between px-5 py-5 text-left sm:px-7">
                <span>
                    <span class="bo-eyebrow block">Colección B</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Documentos institucionales</h3>
                </span>
                <svg class="size-5 transition-transform {{ ($sectionsOpen['documentos'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['documentos'] ?? true)
            <div class="space-y-5 p-4 sm:p-6">

                @can('documentos.gestionar')
                <form wire:submit="addDocumento" class="bo-card grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-5">
                        <label class="bo-label mb-1.5">Nombre del documento</label>
                        <input type="text" wire:model="documentoNombre" class="bo-input w-full text-sm" placeholder="Ej. Modelo caja chica">
                        @error('documentoNombre') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label class="bo-label mb-1.5">Orden</label>
                        <input type="number" min="1" wire:model="documentoOrden" class="bo-input w-full text-sm">
                        @error('documentoOrden') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="bo-label mb-1.5">Archivo</label>
                        <input type="file" wire:model="documentoArchivo" class="bo-input w-full text-sm">
                        @error('documentoArchivo') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="bo-btn bo-btn--primary w-full whitespace-nowrap" wire:loading.attr="disabled" wire:target="addDocumento,documentoArchivo">
                            Incorporar al registro
                        </button>
                    </div>
                    <p class="bo-hint md:col-span-12">PDF, DOC o DOCX · máximo 20 MB</p>
                </form>
                @endcan

                <div class="bo-card overflow-x-auto">
                    <table class="bo-table min-w-full text-sm">
                        <thead >
                            <tr>
                                <th class="w-20 px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">N°</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Documento</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Ingreso</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Gestión</th>
                            </tr>
                        </thead>
                        <tbody >
                            @forelse ($documentos as $i => $item)
                                <tr wire:key="doc-{{ $item['id'] }}">
                                    <td class="px-5 py-3.5"><span class="font-mono text-sm font-bold">{{ str_pad((string) $item['orden'], 2, '0', STR_PAD_LEFT) }}</span></td>
                                    <td class="px-5 py-3.5">
                                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="inline-flex items-center gap-3 font-medium transition">
                                            <span class="flex size-8 items-center justify-center rounded-md"><i class="far fa-file-alt"></i></span>
                                            <span>{{ $item['nombre'] }}</span>
                                        </a>
                                    </td>
                                    <td class="px-5 py-3.5"><span class="font-mono text-xs">{{ $item['created_at'] ?? 'Migrado' }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        @can('documentos.gestionar')<button type="button" wire:click="removeDocumento({{ $item['id'] }})" wire:confirm="El documento se archivará y dejará de mostrarse públicamente. ¿Continuar?" class="bo-btn-icon" title="Archivar" aria-label="Archivar">
                                            <i class="fas fa-trash"></i>
                                        </button>@endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-4 py-6 text-center">Sin documentos cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="bo-section bo-accent-teal overflow-hidden">
            <button type="button" wire:click="toggleSection('migracion')" class="bo-section-toggle flex w-full items-center justify-between px-5 py-5 text-left sm:px-7">
                <span>
                    <span class="bo-eyebrow block">Registro C</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Migración Unijud</h3>
                </span>
                <svg class="size-5 transition-transform {{ ($sectionsOpen['migracion'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['migracion'] ?? true)
            <div class="space-y-5 p-4 sm:p-6">

                <form wire:submit="addTribunal" class="bo-card grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-9">
                        <label class="bo-label mb-1.5">Nombre del tribunal</label>
                        <input type="text" wire:model="tribunalNombre" class="bo-input w-full text-sm" placeholder="Ej. Juzgado de Garantía de Santiago">
                        @error('tribunalNombre') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="bo-btn bo-btn--primary w-full whitespace-nowrap" wire:loading.attr="disabled" wire:target="addTribunal">
                            Incorporar tribunal
                        </button>
                    </div>
                </form>

                <div class="bo-card overflow-x-auto">
                    <table class="bo-table min-w-full text-sm">
                        <thead >
                            <tr>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Tribunal migrado</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Gestión</th>
                            </tr>
                        </thead>
                        <tbody >
                            @forelse ($tribunales as $i => $item)
                                <tr wire:key="trib-{{ $i }}">
                                    <td class="px-5 py-3.5"><span class="inline-flex items-center gap-3 font-medium"><span class="flex size-8 items-center justify-center rounded-md"><i class="fas fa-landmark"></i></span>{{ $item['nombre'] }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button type="button" wire:click="removeTribunal({{ $i }})" wire:confirm="El tribunal se quitará del listado. ¿Continuar?" class="bo-btn-icon" title="Quitar" aria-label="Quitar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="px-4 py-6 text-center">Sin tribunales cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="bo-section bo-accent-violet overflow-hidden">
            <button type="button" wire:click="toggleSection('encargados')" class="bo-section-toggle flex w-full items-center justify-between px-5 py-5 text-left sm:px-7">
                <span>
                    <span class="bo-eyebrow block">Registro D</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">N° Encargado Audiencia</h3>
                </span>
                <svg class="size-5 transition-transform {{ ($sectionsOpen['encargados'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['encargados'] ?? true)
            <div class="space-y-5 p-4 sm:p-6">

                <form wire:submit="addEncargadoAudiencia" class="bo-card grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-6">
                        <label class="bo-label mb-1.5">Nombre</label>
                        <input type="text" wire:model="encargadoAudienciaNombre" class="bo-input w-full text-sm" placeholder="Ej. Leonardo Morales">
                        @error('encargadoAudienciaNombre') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3">
                        <label class="bo-label mb-1.5">Números asignados</label>
                        <input type="text" wire:model="encargadoAudienciaNumeros" class="bo-input w-full text-sm" placeholder="Ej. 1-6">
                        @error('encargadoAudienciaNumeros') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="bo-btn bo-btn--primary w-full whitespace-nowrap" wire:loading.attr="disabled" wire:target="addEncargadoAudiencia">
                            Incorporar encargado
                        </button>
                    </div>
                </form>

                <div class="bo-card overflow-x-auto">
                    <table class="bo-table min-w-full text-sm">
                        <thead >
                            <tr>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Encargado</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Rango asignado</th>
                                <th class="w-28 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Gestión</th>
                            </tr>
                        </thead>
                        <tbody >
                            @forelse ($encargadosAudiencia as $i => $item)
                                <tr wire:key="enc-{{ $i }}">
                                    <td class="px-5 py-3.5"><span class="inline-flex items-center gap-3 font-medium"><span class="flex size-8 items-center justify-center rounded-md"><i class="fas fa-user"></i></span>{{ $item['nombre'] }}</span></td>
                                    <td class="px-5 py-3.5"><span class="bo-chip font-mono text-xs font-semibold">{{ $item['numeros'] }}</span></td>
                                    <td class="px-5 py-3.5 text-right">
                                        <button type="button" wire:click="removeEncargadoAudiencia({{ $i }})" wire:confirm="El encargado se quitará del listado. ¿Continuar?" class="bo-btn-icon" title="Quitar" aria-label="Quitar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center">Sin encargados cargados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>

        <section class="bo-section bo-accent-amber overflow-hidden">
            <div class="px-5 py-5 sm:px-7">
                <span class="bo-eyebrow block">Registro E</span>
                <h3 class="mt-1 text-xl font-semibold tracking-tight">Buscador de sentencias</h3>
            </div>
            <div class="space-y-5 p-4 sm:p-6">
                <p class="text-sm">Incorpora un archivo Excel para actualizar el buscador. Las filas ya existentes se omiten automáticamente.</p>

                <form wire:submit="importSentencias" class="bo-card grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-9">
                        <label class="bo-label mb-1.5">Archivo Excel</label>
                        <input type="file" wire:model="sentenciasExcel" class="bo-input w-full text-sm" accept=".xlsx">
                        @error('sentenciasExcel') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="md:col-span-3 md:self-end">
                        <button type="submit" class="bo-btn bo-btn--primary w-full whitespace-nowrap" wire:loading.attr="disabled" wire:target="importSentencias,sentenciasExcel">
                            Importar Excel
                        </button>
                    </div>
                    <p class="bo-hint md:col-span-12">Formato admitido: XLSX · máximo 30 MB</p>
                </form>
            </div>
        </section>

        <section class="bo-section bo-accent-cyan overflow-hidden">
            <button type="button" wire:click="toggleSection('accesos')" class="bo-section-toggle flex w-full items-center justify-between px-5 py-5 text-left sm:px-7">
                <span>
                    <span class="bo-eyebrow block">Registro F</span>
                    <h3 class="mt-1 text-xl font-semibold tracking-tight">Accesos directos</h3>
                </span>
                <svg class="size-5 transition-transform {{ ($sectionsOpen['accesos'] ?? true) ? 'rotate-180' : '' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            @if ($sectionsOpen['accesos'] ?? true)
            <div class="space-y-5 p-4 sm:p-6">
                <p class="text-sm">Enlaces de la página pública «Accesos directos». Los ocultos no se muestran al público. Los dominios <span class="font-mono">.pjud</span> se marcan solos como «Solo red PJUD».</p>

                <form wire:submit="saveAcceso" class="bo-card grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-end sm:p-5">
                    <div class="md:col-span-3">
                        <label for="accesoGrupo" class="bo-label mb-1.5">Grupo</label>
                        <input id="accesoGrupo" type="text" wire:model="accesoGrupo" list="accesos-grupos" class="bo-input w-full text-sm" placeholder="Ej. Sistemas judiciales">
                        <datalist id="accesos-grupos">
                            @foreach ($accesos->pluck('grupo')->unique() as $grupo)
                                <option value="{{ $grupo }}"></option>
                            @endforeach
                        </datalist>
                        @error('accesoGrupo') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-3">
                        <label for="accesoNombre" class="bo-label mb-1.5">Nombre</label>
                        <input id="accesoNombre" type="text" wire:model="accesoNombre" class="bo-input w-full text-sm" placeholder="Ej. Gestión Penal 1">
                        @error('accesoNombre') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-4">
                        <label for="accesoUrl" class="bo-label mb-1.5">URL</label>
                        <input id="accesoUrl" type="url" wire:model="accesoUrl" class="bo-input w-full text-sm" placeholder="https://…">
                        @error('accesoUrl') <p class="bo-error mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="flex gap-2 md:col-span-2 md:self-end">
                        <button type="submit" class="bo-btn bo-btn--primary w-full whitespace-nowrap" wire:loading.attr="disabled" wire:target="saveAcceso">
                            {{ $editingAccesoId ? 'Guardar' : 'Agregar' }}
                        </button>
                        @if ($editingAccesoId)
                            <button type="button" wire:click="cancelAcceso" class="bo-btn bo-btn--secondary whitespace-nowrap">Cancelar</button>
                        @endif
                    </div>
                </form>

                <div class="bo-card overflow-x-auto">
                    <table class="bo-table min-w-full text-sm">
                        <thead >
                            <tr>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Acceso</th>
                                <th class="px-5 py-3 text-left font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Grupo</th>
                                <th class="w-56 px-5 py-3 text-right font-mono text-[10px] font-bold uppercase tracking-[0.14em]">Gestión</th>
                            </tr>
                        </thead>
                        <tbody >
                            @forelse ($accesos as $acceso)
                                <tr wire:key="acceso-{{ $acceso->id }}" @class(['bo-row-selected' => $editingAccesoId === $acceso->id, 'opacity-60' => ! $acceso->activo])>
                                    <td class="px-5 py-3.5">
                                        <span class="block font-medium">{{ $acceso->nombre }}
                                            @unless ($acceso->activo) <span class="bo-chip ml-1 text-xs font-bold uppercase">Oculto</span> @endunless
                                            @if ($acceso->soloRedInterna()) <span class="bo-chip ml-1 text-xs font-bold">Solo red PJUD</span> @endif
                                        </span>
                                        <a href="{{ $acceso->url }}" target="_blank" rel="noopener" class="font-mono text-xs">{{ $acceso->host() }}</a>
                                    </td>
                                    <td class="px-5 py-3.5">{{ $acceso->grupo }}</td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex justify-end gap-1.5">
                                            <button type="button" wire:click="moveAcceso({{ $acceso->id }}, -1)" @disabled($loop->first) class="bo-btn-icon" title="Subir" aria-label="Subir {{ $acceso->nombre }}"><i class="fas fa-arrow-up"></i></button>
                                            <button type="button" wire:click="moveAcceso({{ $acceso->id }}, 1)" @disabled($loop->last) class="bo-btn-icon" title="Bajar" aria-label="Bajar {{ $acceso->nombre }}"><i class="fas fa-arrow-down"></i></button>
                                            <button type="button" wire:click="toggleAcceso({{ $acceso->id }})" class="bo-btn-icon" title="{{ $acceso->activo ? 'Ocultar' : 'Mostrar' }}" aria-label="{{ $acceso->activo ? 'Ocultar' : 'Mostrar' }} {{ $acceso->nombre }}"><i class="fas {{ $acceso->activo ? 'fa-eye-slash' : 'fa-eye' }}"></i></button>
                                            <button type="button" wire:click="editAcceso({{ $acceso->id }})" class="bo-btn-icon" title="Editar" aria-label="Editar {{ $acceso->nombre }}"><i class="fas fa-pen"></i></button>
                                            <button type="button" wire:click="removeAcceso({{ $acceso->id }})" wire:confirm="El acceso se eliminará. ¿Continuar?" class="bo-btn-icon" title="Eliminar" aria-label="Eliminar {{ $acceso->nombre }}"><i class="fas fa-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center">Sin accesos cargados. Agrega el primero con el formulario de arriba.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </section>
    </div>
</div>
