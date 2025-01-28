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
                                            <th scope="col" style="min-width: 150px;">Accion</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data as $data)
                                            <tr>
                                                <!--<td>{{ $data['id'] ?? 'N/A' }}</td>-->
                                                <td class="text-break">{{ $data['Numentregado'] ?? 'N/A' }}</td>
                                                <td>{{ $data['año'] ?? 'N/A' }}</td>
                                                <td class="text-wrap">{{ $data['CausaAsig'] ?? 'N/A' }}</td>
                                                <td class="text-truncate">{{ $data['UserSolicitante'] ?? 'N/A' }}</td>
                                                <td class="text-truncate">{{ $data['UserDirigido'] ?? 'N/A' }}</td>
                                                <td><form action="{{ route('Oficio.delete', ['id' => $data['id']])  }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de eliminar este oficio?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-danger">Eliminar</button>
                                                    </form>
                                                </td>
                                            </tr>
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
    @push('styles')
        <!-- DataTables CSS -->
        <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
        <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">
    @endpush

    @push('scripts')
        <!-- DataTables JS -->
        <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        
        <script>
             
        
        </script>
    @endpush
</x-app-layout>