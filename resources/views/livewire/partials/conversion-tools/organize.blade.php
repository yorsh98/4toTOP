<div wire:key="organizer-{{ $format }}">
    <div wire:ignore x-data="pdfOrganizer({{ App\Conversions\OrganizePdfTool::MAX_PAGES }})"
         x-on:change.document="if ($event.target.id === 'conversion-file') load($event.target.files[0])"
         x-on:conversion-queued.window="load(null)">
        <p x-show="loading" style="display: none" class="text-sm font-medium text-sky-700" role="status">Cargando páginas… <span x-text="pages.length"></span></p>
        <p x-show="error" x-text="error" style="display: none" class="text-sm text-red-700" role="alert"></p>
        <template x-if="pages.length">
            <div>
                <p class="mb-2 text-sm text-slate-700">Arrastra las páginas para ordenarlas, o usa los botones de cada una. <span x-text="pages.length"></span> páginas en el resultado.</p>
                <ol class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                    <template x-for="(item, index) in pages" :key="item.page + '-' + index">
                        <li draggable="true" x-on:dragstart="dragged = index" x-on:dragover.prevent x-on:drop.prevent="drop(index)"
                            class="flex cursor-grab flex-col items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 p-2 active:cursor-grabbing">
                            <div class="flex h-36 w-full items-center justify-center overflow-hidden">
                                <img :src="item.src" :alt="'Página ' + item.page" :style="'transform: rotate(' + item.rotation + 'deg)'" class="max-h-32 max-w-full shadow transition-transform">
                            </div>
                            <span class="text-xs font-semibold text-slate-700" x-text="'Página ' + item.page"></span>
                            <span class="flex gap-1">
                                <button type="button" x-on:click="move(index, index - 1)" :disabled="index === 0" :aria-label="'Mover antes la página ' + item.page" class="rounded p-1.5 text-slate-600 hover:bg-white hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-30"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i></button>
                                <button type="button" x-on:click="rotate(index)" :aria-label="'Rotar la página ' + item.page" class="rounded p-1.5 text-slate-600 hover:bg-white hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700"><i class="fa-solid fa-rotate-right" aria-hidden="true"></i></button>
                                <button type="button" x-on:click="remove(index)" :aria-label="'Quitar la página ' + item.page" class="rounded p-1.5 text-slate-600 hover:bg-white hover:text-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-red-700"><i class="fa-solid fa-trash-can" aria-hidden="true"></i></button>
                                <button type="button" x-on:click="move(index, index + 1)" :disabled="index === pages.length - 1" :aria-label="'Mover después la página ' + item.page" class="rounded p-1.5 text-slate-600 hover:bg-white hover:text-sky-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-sky-700 disabled:opacity-30"><i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                            </span>
                        </li>
                    </template>
                </ol>
            </div>
        </template>
    </div>
    @error('options.plan') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>
