<x-app-layout>
    @push('styles')
        <link href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css" rel="stylesheet">
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel Administrativo del listado de Oficios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <table id="miTabla" class="table table-striped">
                        <thead>
                            <tr>
                                <th>Número Entregado</th>
                                <th>Año</th>
                                <th>Causa Asignada</th>
                                <th>Solicitante</th>
                                <th>Dirigido a</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $data)
                            <tr>
                                <td>{{ $data['Numentregado'] ?? 'N/A' }}</td>
                                <td>{{ $data['año'] ?? 'N/A' }}</td>
                                <td>{{ $data['CausaAsig'] ?? 'N/A' }}</td>
                                <td>{{ $data['UserSolicitante'] ?? 'N/A' }}</td>
                                <td>{{ $data['UserDirigido'] ?? 'N/A' }}</td>
                                <td>
                                    <!-- Botón para abrir el modal de modificación -->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateOficioModal{{ $data['id'] }}">Modificar</button>

                                    <!-- Formulario de eliminación -->
                                    <form action="{{ route('Oficio.delete', ['id' => $data['id']]) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este oficio?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                    </form>
                                </td>
                            </tr>

                            <!-- Modal de actualización para este oficio -->
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
                                                    <input type="text" class="form-control" id="CausaAsig{{ $data['id'] }}" name="CausaAsig" value="{{ $data['CausaAsig'] }}" pattern="^[a-zA-Z0-9\s\-]{1,10}$">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="UserSolicitante{{ $data['id'] }}" class="form-label">Solicitante</label>
                                                    <input type="text" class="form-control" id="UserSolicitante{{ $data['id'] }}" name="UserSolicitante" value="{{ $data['UserSolicitante'] }}">
                                                </div>
                                                <div class="mb-3">
                                                    <label for="UserDirigido{{ $data['id'] }}" class="form-label">Dirigido a</label>
                                                    <input type="text" class="form-control" id="UserDirigido{{ $data['id'] }}" name="UserDirigido" value="{{ $data['UserDirigido'] }}" pattern="^[a-zA-Z0-9\s\-]{1,10}$">
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
        </div>
    </div>

    @push('scripts')
        <!-- Asegúrate de cargar jQuery primero, ya que DataTables depende de ello -->
        <script src="https://code.jquery.com/jquery-3.7.1.js"   integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4="   crossorigin="anonymous"></script>
        <script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js"></script>
        

        <script>
            $(document).ready(function() {
                var table = $('#miTabla').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('Oficio.data') }}", // Asegúrate de que esta ruta está bien definida
                    columns: [
                        { data: 'Numentregado', name: 'Numentregado', searchable: true },
                        { data: 'año', name: 'año' },
                        { data: 'CausaAsig', name: 'CausaAsig' },
                        { data: 'UserSolicitante', name: 'UserSolicitante' },
                        { data: 'UserDirigido', name: 'UserDirigido' },
                        { data: 'actions', name: 'actions', orderable: false, searchable: false }
                    ],
                    language: {
                        "sProcessing":     "Procesando...",
                        "sLengthMenu":     "",
                        "sZeroRecords":    "No se encontraron resultados",
                        "sEmptyTable":     "No hay datos disponibles en la tabla",
                        "sInfo":           "Mostrando _START_ a _END_ de _TOTAL_ registros",
                        "sInfoEmpty":      "Mostrando 0 a 0 de 0 registros",
                        "sInfoFiltered":   "(filtrado de _MAX_ registros en total)",
                        "sSearch":         "Buscar:",
                        "sUrl":            "",
                        "oPaginate": {
                            "sFirst":    "Primera",
                            "sPrevious": "Anterior",
                            "sNext":     "Siguiente",
                            "sLast":     "Última"
                        }
                    }    
                });
            });
        </script>
    @endpush
</x-app-layout>
