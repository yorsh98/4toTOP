<x-app-layout>

    @push('styles')
        
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel administrativo de GUIAS TELEFONICAS') }}
        </h2>
    </x-slot>
    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          
        </div>
    </div>
    <div class="py-1">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900" >
                <!--ESPACIO PARA MODULO LIVEWIRE -->
                    <livewire:tabla-guias :modo="'full'" />
                    
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
                                <option value="12">12. Otros Tribunales</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-success">Actualizar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @push('scripts')

    @endpush
</x-app-layout>
