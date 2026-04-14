<x-slot:header>
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Panel Administrativo de los Turnos</h2>
</x-slot:header>

<div class="container mx-auto p-2 max-w-5xl">
    <div class="mb-4 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        Los listados se cargan desde el catalogo global de funcionarios por rol.
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="space-y-4">
        <details class="bg-white shadow-md rounded-lg">
            <summary class="cursor-pointer select-none px-6 py-4 flex items-center justify-between">
                <span class="text-lg font-semibold text-gray-700">Turno 1</span>
                <span class="material-icons text-gray-500">turno semanal</span>
            </summary>
            <div class="px-6 pb-6">
                <form wire:submit="saveTurno1" class="space-y-4">
                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'TM'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno1.{{ $field }}"
                                list="magistradosList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione magistrado"
                            >
                            @error('turno1.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'TF'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno1.{{ $field }}"
                                list="funcionariosList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione funcionario"
                            >
                            @error('turno1.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'TJ'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno1.{{ $field }}"
                                list="jefaturasList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione jefatura"
                            >
                            @error('turno1.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    <button type="submit" class="btn btn-success py-2 mt-2" wire:loading.attr="disabled" wire:target="saveTurno1">
                        <span wire:loading.remove wire:target="saveTurno1">Enviar Turno 1</span>
                        <span wire:loading wire:target="saveTurno1">Guardando Turno 1...</span>
                    </button>
                </form>
            </div>
        </details>

        <details class="bg-white shadow-md rounded-lg">
            <summary class="cursor-pointer select-none px-6 py-4 flex items-center justify-between">
                <span class="text-lg font-semibold text-gray-700">Turno 2 ACD</span>
                <span class="material-icons text-gray-500">Turno Audiencia de Control Detenciones</span>
            </summary>
            <div class="px-6 pb-6">
                <form wire:submit="saveTurno2Acd" class="space-y-4">
                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'ACDM'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno2.{{ $field }}"
                                list="magistradosList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione magistrado"
                            >
                            @error('turno2.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'ACDF'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno2.{{ $field }}"
                                list="funcionariosList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione funcionario"
                            >
                            @error('turno2.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    @for ($i = 1; $i <= 3; $i++)
                        @php $field = 'ACDJ'.$i; @endphp
                        <div>
                            <label for="{{ $field }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $field }}"
                                wire:model="turno2.{{ $field }}"
                                list="jefaturasList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione jefatura"
                            >
                            @error('turno2.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    <div>
                        <label for="FECHA" class="block text-sm font-medium text-gray-600">Fecha proximo turno</label>
                        <input
                            type="text"
                            id="FECHA"
                            wire:model="turno2.FECHA"
                            class="w-full border rounded-md p-2"
                            placeholder="Ej: 18, 19 y 20 de Abril"
                        >
                        @error('turno2.FECHA') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="btn btn-success py-2 mt-2" wire:loading.attr="disabled" wire:target="saveTurno2Acd">
                        <span wire:loading.remove wire:target="saveTurno2Acd">Enviar Turno 2</span>
                        <span wire:loading wire:target="saveTurno2Acd">Guardando Turno 2...</span>
                    </button>
                </form>
            </div>
        </details>

        <details class="bg-white shadow-md rounded-lg">
            <summary class="cursor-pointer select-none px-6 py-4 flex items-center justify-between">
                <span class="text-lg font-semibold text-gray-700">Turno 3</span>
                <span class="material-icons text-gray-500">Turno Programacion de Audiencias</span>
            </summary>
            <div class="px-6 pb-6">
                <form wire:submit="saveTurno3" class="space-y-4">
                    @for ($i = 1; $i <= 3; $i++)
                        @php
                            $field = 'TM'.$i;
                            $id = 'T3_'.$field;
                        @endphp
                        <div>
                            <label for="{{ $id }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                            <input
                                type="text"
                                id="{{ $id }}"
                                wire:model="turno3.{{ $field }}"
                                list="magistradosList"
                                class="w-full border rounded-md p-2"
                                placeholder="Escriba o seleccione magistrado"
                            >
                            @error('turno3.'.$field) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endfor

                    <button type="submit" class="btn btn-success py-2 mt-2" wire:loading.attr="disabled" wire:target="saveTurno3">
                        <span wire:loading.remove wire:target="saveTurno3">Enviar Turno 3</span>
                        <span wire:loading wire:target="saveTurno3">Guardando Turno 3...</span>
                    </button>
                </form>
            </div>
        </details>
    </div>

    <datalist id="magistradosList">
        @foreach ($magistrados as $nombre)
            <option value="{{ $nombre }}">
        @endforeach
    </datalist>

    <datalist id="funcionariosList">
        @foreach ($funcionarios as $nombre)
            <option value="{{ $nombre }}">
        @endforeach
    </datalist>

    <datalist id="jefaturasList">
        @foreach ($jefaturas as $nombre)
            <option value="{{ $nombre }}">
        @endforeach
    </datalist>
</div>
