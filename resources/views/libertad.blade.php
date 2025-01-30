<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Administración Base de Datos de Oficios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("Estás en la Administración de BD Oficios") }}
                    
                    <!-- Tabla con DataTables -->
                    @if (!empty($data))
                        <div class="improved-table">
                            <div class="table-responsive" style="margin: 0 -15px;"> <!-- Margen negativo para expandirse -->
                                <table class="table table-hover table-bordered align-middle w-100"> <!-- w-100 para ancho completo -->
                                    <thead class="thead-dark">
                                        <tr>
                                            <th scope="col" style="min-width: 150px;">Número de Oficio</th>
                                            <th scope="col" style="min-width: 80px;">Año</th>
                                            <th scope="col" style="min-width: 250px;">Causa Asignada</th>
                                            <th scope="col" style="min-width: 200px;">Solicitante</th>
                                            <th scope="col" style="min-width: 250px;">Dirigido a</th>
                                            <th scope="col" style="min-width: 150px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data as $data)
                                            <tr>
                                                <td class="text-break">{{ $data['Numentregado'] ?? 'N/A' }}</td>
                                                <td>{{ $data['año'] ?? 'N/A' }}</td>
                                                <td class="text-wrap">{{ $data['CausaAsig'] ?? 'N/A' }}</td>
                                                <td class="text-truncate">{{ $data['UserSolicitante'] ?? 'N/A' }}</td>
                                                <td class="text-truncate">{{ $data['UserDirigido'] ?? 'N/A' }}</td>
                                                <td class="d-flex gap-1">
                                                    <form action="{{ route('Oficio.delete', ['id' => $data['id']]) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este oficio?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                                    </form>
                                                    
                                                    <!-- Botón para abrir el modal -->
                                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateOficioModal{{ $data['id'] }}">
                                                        Modificar
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Modal para actualizar oficio -->
                                            <div class="modal fade" id="updateOficioModal{{ $data['id'] }}" tabindex="-1" aria-labelledby="updateOficioModalLabel{{ $data['id'] }}" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="updateOficioModalLabel{{ $data['id'] }}">Modificar Oficio</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form action="{{ route('Oficio.update', ['id' => $data['id']]) }}" method="POST">
                                                                @csrf
                                                                @method('PATCH')
                                                                <div class="mb-3">
                                                                    <label for="CausaAsig{{ $data['id'] }}" class="form-label">Causa Asignada</label>
                                                                    <input type="text" class="form-control" id="CausaAsig{{ $data['id'] }}" name="CausaAsig" value="{{ $data['CausaAsig'] }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="UserSolicitante{{ $data['id'] }}" class="form-label">Solicitante</label>
                                                                    <input type="text" class="form-control" id="UserSolicitante{{ $data['id'] }}" name="UserSolicitante" value="{{ $data['UserSolicitante'] }}">
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label for="UserDirigido{{ $data['id'] }}" class="form-label">Dirigido a</label>
                                                                    <input type="text" class="form-control" id="UserDirigido{{ $data['id'] }}" name="UserDirigido" value="{{ $data['UserDirigido'] }}">
                                                                </div>
                                                                <button type="submit" class="btn btn-success" onclick="return confirm('¿Estás seguro de ACTUALIZAR este oficio?')" >Guardar Cambios</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning text-center">
                            No hay datos disponibles.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
