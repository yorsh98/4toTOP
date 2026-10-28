<x-slot:header>
    <x-page-heading eyebrow="Audios · Salas" title="Audios de salas" />
</x-slot:header>

<div class="py-2">
    <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
        <x-flash-notifications />

        <div class="bo-card bo-section p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <label for="anio" class="mb-1 block text-sm font-medium bo-strong">Año</label>
                    <select id="anio" wire:model.live="anio" class="bo-select text-sm">
                        @foreach ($anios as $itemAnio)
                            <option value="{{ $itemAnio }}">{{ $itemAnio }}</option>
                        @endforeach
                    </select>
                    @error('anio') <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                </div>

                @can('audios_salas.gestionar')
                    <button type="button" wire:click="abrirConfiguracion" class="bo-btn bo-btn--secondary">
                        <i class="fa-solid fa-gear mr-1"></i> Configuración
                    </button>
                @endcan
            </div>

            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($salas as $sala)
                    @if ($enlaces[$sala] !== '')
                        <a href="{{ $enlaces[$sala] }}" target="_blank" rel="noopener noreferrer" class="bo-btn bo-btn--secondary justify-between px-5 py-4 text-base">
                            Sala {{ $sala }}
                            <i class="fa-solid fa-arrow-up-right-from-square text-sm" aria-hidden="true"></i>
                        </a>
                    @else
                        <div class="bo-panel-subtle bo-muted flex items-center justify-between rounded-xl px-5 py-4 text-base font-semibold" aria-disabled="true">
                            Sala {{ $sala }}
                            <span class="text-xs font-normal">Sin enlace</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </div>

        @can('audios_salas.gestionar')
            @if ($configuracionAbierta)
                <form wire:submit="guardar" class="bo-card bo-section p-6">
                    <h3 class="text-lg font-semibold bo-strong">Configuración de enlaces para {{ $anio }}</h3>
                    <p class="mt-1 text-sm bo-muted">Deje vacío un campo para quitar su enlace.</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        @foreach ($salas as $sala)
                            <div>
                                <label for="sala-{{ $sala }}" class="mb-1 block text-sm font-medium bo-strong">Sala {{ $sala }}</label>
                                <input id="sala-{{ $sala }}" type="url" wire:model="enlaces.{{ $sala }}" placeholder="https://repositorio..." class="bo-input w-full text-sm">
                                @error("enlaces.$sala") <p class="mt-1 text-xs bo-text--danger">{{ $message }}</p> @enderror
                            </div>
                        @endforeach
                    </div>

                    <button type="submit" class="bo-btn bo-btn--primary mt-5" wire:loading.attr="disabled" wire:target="guardar">
                        Guardar enlaces
                    </button>
                </form>
            @endif
        @endcan
    </div>
</div>
