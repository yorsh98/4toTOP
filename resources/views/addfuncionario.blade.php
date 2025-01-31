<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Listado de Funcionarios para solicitar numero de Libertades u Oficio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ route('addfuncionario.store') }}" method="POST" class="input-group" novalidate>
                        @csrf
                        <input class="form-control" name="nombre" type="text" placeholder="Nuevo Funcionario" aria-label="Nuevo Funcionario" required pattern="^[a-zA-Z0-9\s-]+$">
                        <button type="submit" class="btn btn-success">Agregar</button>
                    </form>
                        <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                            <table class="table" style="max-height: 400px; overflow-y: auto;">
                                <thead>
                                    <tr>
                                        <th scope="col">Funcionario</th>
                                        <th scope="col">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($solicitantes as $solicitante)
                                        <tr>
                                            <td>{{ $solicitante->nombre }}</td>
                                            <td>
                                                <form action="{{ route('addfuncionario.destroy', $solicitante->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
