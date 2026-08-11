<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Audios de Salas</h2>
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 sm:px-6 lg:px-8">
        @if (session('ok'))
            <div class="rounded-md bg-green-100 px-4 py-2 text-sm text-green-800">{{ session('ok') }}</div>
        @endif

        <div class="bg-white p-6 shadow-sm sm:rounded-lg">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <label for="anio" class="mb-1 block text-sm font-medium text-gray-700">Año</label>
                    <select id="anio" wire:model.live="anio" class="rounded-md border-gray-300 text-sm">
                        @foreach ($anios as $itemAnio)
                            <option value="{{ $itemAnio }}">{{ $itemAnio }}</option>
                        @endforeach
                    </select>
                    @error('anio') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @can('audios_salas.gestionar')
                    <button type="button" wire:click="abrirConfiguracion" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-gear mr-1"></i> Configuración
                    </button>
                @endcan
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($salas as $sala)
                    @if ($enlaces[$sala] !== '')
                        <a href="{{ $enlaces[$sala] }}" target="_blank" rel="noopener noreferrer" class="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-5 py-4 text-base font-semibold text-slate-800 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-800">
                            Sala {{ $sala }}
                            <i class="fa-solid fa-arrow-up-right-from-square text-sm" aria-hidden="true"></i>
                        </a>
                    @else
                        <div class="flex items-center justify-between rounded-xl border border-dashed border-slate-300 bg-slate-50 px-5 py-4 text-base font-semibold text-slate-500" aria-disabled="true">
                            Sala {{ $sala }}
                            <span class="text-xs font-normal">Sin enlace</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        @can('audios_salas.gestionar')
            @if ($configuracionAbierta)
                <form wire:submit="guardar" class="bg-white p-6 shadow-sm sm:rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900">Configuración de enlaces para {{ $anio }}</h3>
                    <p class="mt-1 text-sm text-slate-600">Deje vacío un campo para quitar su enlace.</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($salas as $sala)
                            <div>
                                <label for="sala-{{ $sala }}" class="mb-1 block text-sm font-medium text-gray-700">Sala {{ $sala }}</label>
                                <input id="sala-{{ $sala }}" type="url" wire:model="enlaces.{{ $sala }}" placeholder="https://repositorio..." class="w-full rounded-md border-gray-300 text-sm">
                                @error("enlaces.$sala") <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="mt-5 rounded-md bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700" wire:loading.attr="disabled" wire:target="guardar">
                        Guardar enlaces
                    </button>
                </form>
            @endif
        @endcan
    </div>
</div>
