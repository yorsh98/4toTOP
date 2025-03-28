<x-app-layout>
<x-insertbootstrap />
    @push('styles')
        
        <link href="https://cdn.datatables.net/2.2.2/css/dataTables.dataTables.min.css" rel="stylesheet">
        
        

    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel administrativo de GUIAS TELEFONICAS') }}
        </h2>
    </x-slot>
    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <h4 class="alert-heading">¡Error!</h4>
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <h3>Ingrese nueva GUIA </h3>
                    <form action="{{ route('guias.store') }}" method="POST" class="input-group pt-2" novalidate>
                        @csrf
                        @method('POST')
                        <input class="form-control" name="nombre" type="text" placeholder="Ingrese Nombre" value="{{ old('nombre') }}" aria-label="Nuevo Funcionario" required pattern="^[a-zA-Z0-9\s-]+$" >
                        <input class="form-control" name="rut" type="text" placeholder="Ingrese RUT" value="{{ old('rut') }}" aria-label="Nuevo Funcionario"  pattern="^[a-zA-Z0-9\s-]+$">
                        <input class="form-control" name="correo" type="email" placeholder="Ingrese Correo" value="{{ old('correo') }}" aria-label="Nuevo Funcionario" pattern="^[a-zA-Z0-9\s-]+$">
                        <input class="form-control" name="tel1" type="number" placeholder="N° Telefonico 1" value="{{ old('telefono 1') }}" aria-label="Nuevo Funcionario"  pattern="[0-9]{9,12}" maxlength="12" >
                        <input class="form-control" name="tel2" type="number" placeholder="N° Telefonico 2" value="{{ old('telefono opcional') }}" aria-label="Nuevo Funcionario"  pattern="[0-9]{9,12}" maxlength="12" >
                        <select class="form-select" name="institucion" aria-label="Nuevo Funcionario" required>
                            <option value="" disabled selected >Institucion</option>
                            <option value="1">1. 4toTOPSTGO</option>
                            <option value="2">2. Gendarmeria</option>
                            <option value="3">3. Fiscalia</option>
                            <option value="4">4. CAPJ</option>
                            <option value="5">5. Zonal STGO</option>
                            <option value="6">6. Defensoria</option>
                            <option value="7">7. Min. Interior</option>
                            <option value="8">8. C.D. Estado</option>
                            <option value="9">9. Defensores Privados</option>
                            <option value="10">10. PDI/Carabineros</option>
                            <option value="11">11. SML</option>
                            
                        </select>
                        <button type="submit" class="btn btn-success">Agregar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="py-1">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif
                <table id="TablaGuias" class="table table-striped">
                        <thead>
                            <tr>
                                <th>NOMBRE</th>
                                <th>RUT</th>
                                <th>EMAIL</th>
                                <th>TELEFONO 1</th>
                                <th>TELEFONO 2</th>
                                <th>INSTITUCION</th>
                                <th>ACCIONES</th>
                            </tr>
                        </thead>
                        
                        
                    </table>
                </div>
            </div>
        </div>
    </div>
        <!-- Modal para editar guías -->
                    
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Guía</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" id="edit-id">
                        <div class="mb-3">
                            <label for="edit-nombre" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="edit-nombre" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-rut" class="form-label">RUT</label>
                            <input type="text" class="form-control" id="edit-rut" >
                        </div>
                        <div class="mb-3">
                            <label for="edit-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="edit-email">
                        </div>
                        <div class="mb-3">
                            <label for="edit-telefono1" class="form-label">Teléfono 1</label>
                            <input type="text" class="form-control" id="edit-telefono1">
                        </div>
                        <div class="mb-3">
                            <label for="edit-telefono2" class="form-label">Teléfono 2</label>
                            <input type="text" class="form-control" id="edit-telefono2">
                        </div>
                        <div class="mb-3">
                            <label for="edit-institucion" class="form-label">Institución</label>
                            <select class="form-control" name="institucion" id="edit-institucion" aria-label="Nuevo Funcionario" required>
                                <option value="" disabled selected >Institucion</option>
                                <option value="1">1. 4toTOPSTGO</option>
                                <option value="2">2. Gendarmeria</option>
                                <option value="3">3. Fiscalia</option>
                                <option value="4">4. CAPJ</option>
                                <option value="5">5. Zonal STGO</option>
                                <option value="6">6. Defensoria</option>
                                <option value="7">7. Min. Interior</option>
                                <option value="8">8. C.D. Estado</option>
                                <option value="9">9. Defensores Privados</option>
                                <option value="10">10. PDI/Carabineros</option>
                                <option value="11">11. SML</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')
        <!-- Asegúrate de cargar jQuery primero, ya que DataTables depende de ello -->
        <script src="https://code.jquery.com/jquery-3.7.1.js" ></script>
        <script src="https://cdn.datatables.net/2.2.2/js/dataTables.min.js" ></script>
        
        <script>
        $(document).ready(function() {
    $('#TablaGuias').DataTable({
        processing: true,
        serverSide: true,
        ajax: "{{ route('guias.datatable') }}",  // Ruta de la API para obtener los datos
        columns: [
            { data: 'nombre_completo', name: 'nombre_completo' },
            { data: 'rut', name: 'rut' },
            { data: 'email', name: 'email' },
            { data: 'telefono1', name: 'telefono1' },
            { data: 'telefono2', name: 'telefono2' },
            { data: 'institucion', name: 'institucion' },
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
    $(document).on('click', '.btn-delete', function() {
        var guiaId = $(this).data('id');
        if (confirm('¿Estás seguro de eliminar esta guía?')) {
            $.ajax({
                url: "/guias/" + guiaId,
                type: "DELETE",
                data: {
                    _token: "{{ csrf_token() }}"
                },
                success: function(response) {
                alert(response.success); // Muestra la alerta en el navegador
                $('#TablaGuias').DataTable().ajax.reload(); // Recargar DataTable
            },
            error: function(xhr) {
                alert("Error al eliminar la guía.");
            }
            });
        }
    });
    $(document).on('click', '.btn-edit', function() {
    var guiaId = $(this).data('id');
    $.ajax({
        url: "/guias/" + guiaId + "/edit",
        type: "GET",
        success: function(response) {
            $('#edit-id').val(response.id);
            $('#edit-nombre').val(response.nombre_completo);
            $('#edit-rut').val(response.rut);
            $('#edit-email').val(response.email);
            $('#edit-telefono1').val(response.telefono1);
            $('#edit-telefono2').val(response.telefono2);
            $('#edit-institucion').val(response.institucion);

            $('#editModal').modal('show'); // Abrir el modal
            }
        });
    }); 
    $('#editForm').submit(function(e) {
        e.preventDefault();
        var formData = $(this).serialize();
        $.ajax({
            url: "/guias/" + $('#edit-id').val(),
            type: "PATCH",
            data: formData,
            success: function(response) {
                $('#editModal').modal('hide'); // Cerrar el modal
                $('#TablaGuias').DataTable().ajax.reload(); // Recargar DataTable
            }
        });
    });             
    $('#editForm').submit(function(e) {
    e.preventDefault();
    
    var guiaId = $('#edit-id').val();

    $.ajax({
        url: "/guias/" + guiaId,
        type: "PATCH",
        data: {
            _token: "{{ csrf_token() }}",
            _method: "PATCH", 
            nombre_completo: $('#edit-nombre').val(),
            rut: $('#edit-rut').val(),
            email: $('#edit-email').val(),
            telefono1: $('#edit-telefono1').val(),
            telefono2: $('#edit-telefono2').val(),
            institucion: $('#edit-institucion').val()
        },
        success: function(response) {
            alert(response.success);
            $('#editModal').modal('hide');
            $('#TablaGuias').DataTable().ajax.reload(); // Recargar tabla
        },
        error: function(xhr) {
            alert("Error al actualizar la guía.");
        }
    });
});


});
        </script>
        

        
            
    
    @endpush
</x-app-layout>
