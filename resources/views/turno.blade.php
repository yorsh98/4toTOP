<x-app-layout>
    
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel Administrativo de los Turnos') }}
        </h2>
    </x-slot>
    


<div class="container mx-auto p-2">
    <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
        <!-- Módulo 1 -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Turno 1</h2>
            <form action="{{ route('turno.update', 1) }}" method="POST" class="space-y-4">
                @csrf 
                @method('PATCH')
                @for ($i = 1; $i <= 3; $i++)
                    <div>
                        <label for="TM{{ $i }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                        <input type="text" name="TM{{ $i }}" id="TM{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno1->{'TM'.$i} ?? '' }}">
                    </div>
                @endfor
                @for ($i = 1; $i <= 3; $i++)
                    <div>
                        <label for="TF{{ $i }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                        <input type="text" name="TF{{ $i }}" id="TF{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno1->{'TF'.$i} ?? '' }}">
                    </div>
                @endfor
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

        <!-- Módulo 2 -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Turno 2 ACD</h2>
            <form action="{{ route('turno.update', 2) }}" method="POST" class="space-y-4">
                @csrf
                @method('PATCH')
                @for ($i = 1; $i <= 3; $i++)
                    <div>
                        <label for="ACDM{{ $i }}" class="block text-sm font-medium text-gray-600">Magistrado {{ $i }}</label>
                        <input type="text" name="ACDM{{ $i }}" id="ACDM{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'ACDM'.$i} ?? '' }}">
                    </div>
                @endfor
                @for ($i = 1; $i <= 3; $i++)
                    <div>
                        <label for="ACDF{{ $i }}" class="block text-sm font-medium text-gray-600">Funcionario {{ $i }}</label>
                        <input type="text" name="ACDF{{ $i }}" id="ACDF{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'ACDF'.$i} ?? '' }}">
                    </div>
                @endfor
                @for ($i = 1; $i <= 3; $i++)
                    <div>
                        <label for="ACDJ{{ $i }}" class="block text-sm font-medium text-gray-600">Jefatura {{ $i }}</label>
                        <input type="text" name="ACDJ{{ $i }}" id="ACDJ{{ $i }}" class="w-full border rounded-md p-2" placeholder="Ingrese Primer nombre apellido" value="{{ $turno2->{'ACDJ'.$i} ?? '' }}">
                    </div>
                @endfor
                <div>
                    <label for="FECHA" class="block text-sm font-medium text-gray-600">FECHA PROXIMO TURNO</label>
                    <input type="text" name="FECHA" id="FECHA" class="w-full border rounded-md p-2" placeholder="EJ: 18, 19 y 20 de Abril" value="{{ $turno2->{'FECHA'} ?? '' }}">
                </div>
                <button type="submit" class="btn btn-success py-2 mt-2">
                    Enviar Turno 2
                </button>
            </form>
        </div>
    </div>
</div>



</x-app-layout>
