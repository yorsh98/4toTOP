<x-app-layout>    
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel Administrativo de los Turnos') }}
        </h2>
    </x-slot>   

    <div class="container mx-auto p-2 max-w-5xl">

        <div class="space-y-4">

            {{-- Módulo 1 (colapsable) --}}
            <details class="bg-white shadow-md rounded-lg">
                <summary class="cursor-pointer select-none px-6 py-4 flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-700">Turno 1</span>
                    <span class="material-icons text-gray-500">turno semanal</span>
                </summary>
                <div class="px-6 pb-6">
                    <form action="{{ route('turno.update', 1) }}" method="POST" class="space-y-4">
                        @csrf 
                        @method('PATCH')

                        {{-- Magistrados --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="TM{{ $i }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                                <input type="text" name="TM{{ $i }}" id="TM{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno1->{'TM'.$i} ?? '' }}">
                            </div>
                        @endfor

                        {{-- Funcionarios --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="TF{{ $i }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                                <input type="text" name="TF{{ $i }}" id="TF{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno1->{'TF'.$i} ?? '' }}">
                            </div>
                        @endfor

                        {{-- Jefaturas --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="TJ{{ $i }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                                <input type="text" name="TJ{{ $i }}" id="TJ{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno1->{'TJ'.$i} ?? '' }}">
                            </div>
                        @endfor

                        <button type="submit" class="btn btn-success py-2 mt-2">
                            Enviar Turno 1
                        </button>
                    </form>
                </div>
            </details>

            {{-- Módulo 2 (colapsable) --}}
            <details class="bg-white shadow-md rounded-lg">
                <summary class="cursor-pointer select-none px-6 py-4 flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-700">Turno 2 ACD</span>
                    <span class="material-icons text-gray-500">Turno Audiencia de Control Detenciones</span>
                </summary>
                <div class="px-6 pb-6">
                    <form action="{{ route('turno.update', 2) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        {{-- Magistrados ACD --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="ACDM{{ $i }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                                <input type="text" name="ACDM{{ $i }}" id="ACDM{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'ACDM'.$i} ?? '' }}">
                            </div>
                        @endfor

                        {{-- Funcionarios ACD --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="ACDF{{ $i }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                                <input type="text" name="ACDF{{ $i }}" id="ACDF{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'ACDF'.$i} ?? '' }}">
                            </div>
                        @endfor

                        {{-- Jefaturas ACD --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="ACDJ{{ $i }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                                <input type="text" name="ACDJ{{ $i }}" id="ACDJ{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'ACDJ'.$i} ?? '' }}">
                            </div>
                        @endfor

                        <div>
                            <label for="FECHA" class="block text-sm font-medium text-gray-600">FECHA PRÓXIMO TURNO</label>
                            <input type="text" name="FECHA" id="FECHA" class="w-full border rounded-md p-2" placeholder="EJ: 18, 19 y 20 de Abril" value="{{ $turno2->{'FECHA'} ?? '' }}">
                        </div>

                        <button type="submit" class="btn btn-success py-2 mt-2">
                            Enviar Turno 2
                        </button>
                    </form>
                </div>
            </details>

            {{-- Módulo 3 (colapsable) -> usa TM1, TM2, TM3 con id=2 (turno 3 utiliza misma porcion en BD de turno ACD) --}}
            <details class="bg-white shadow-md rounded-lg">
                <summary class="cursor-pointer select-none px-6 py-4 flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-700">Turno 3</span>
                    <span class="material-icons text-gray-500">Turno Programacion de Audiencias</span>
                </summary>
                <div class="px-6 pb-6">
                    <form action="{{ route('turno.update', 2) }}" method="POST" class="space-y-4">
                        @csrf
                        @method('PATCH')

                        {{-- SOLO TM1..TM3, leyendo/escribiendo sobre el registro id=2 como pediste --}}
                        @for ($i = 1; $i <= 3; $i++)
                            <div>
                                <label for="TM{{ $i }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                                <input type="text" name="TM{{ $i }}" id="TM{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'TM'.$i} ?? '' }}">
                            </div>
                        @endfor

                        <button type="submit" class="btn btn-success py-2 mt-2">
                            Enviar Turno 3
                        </button>
                    </form>
                </div>
            </details>

        </div>
    </div>
</x-app-layout>
