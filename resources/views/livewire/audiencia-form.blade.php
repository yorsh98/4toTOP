
<div class="max-w-4xl mx-auto p-6 bg-white rounded-lg shadow-md">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        {{ $audienciaId ? 'Editar' : 'Registro de Nueva' }} Audiencia
    </h2>
    
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <form wire:submit.prevent="guardarAudiencia" class="space-y-6">
        <!-- Sección 1: Información Básica -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha*</label>
                <input type="date" wire:model="fecha" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('fecha') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
           <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duración*</label>
                <input type="text" wire:model="duracion" placeholder="Ej: 2 horas, 1 día" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('duracion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div> 
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RIT*</label>
                <input 
                    type="text" 
                    wire:model="rit" 
                    wire:blur="buscarPorRit" 
                    placeholder="Ej: 123-2025" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md"
                >
                @error('rit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
           
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RUC</label>
                <input type="text" wire:model="ruc" placeholder="Opcional" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sala*</label>
                <input type="text" wire:model="sala" placeholder="Ej: Sala 701" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('sala') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación*</label>
                <input type="text" wire:model="ubicacion" placeholder="Ej: Edificio A, Piso 7" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('ubicacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Sección 2: Ubicación y Horario -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hora Inicio*</label>
                <input type="time" wire:model="hora_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('hora_inicio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Audiencia*</label>
                <input type="text" wire:model="tipo_audiencia" list="tiposAudiencia" placeholder="Seleccione o escriba" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <datalist id="tiposAudiencia">
                    @foreach($tiposAudiencia as $tipo)
                        <option value="{{ $tipo }}">
                    @endforeach
                </datalist>
                @error('tipo_audiencia') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- Sección 3: Datos Complementarios -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Zoom</label>
                <input type="text" wire:model="cta_zoom" placeholder="ID de reunión Zoom" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            
        </div>
       <hr> 
        <!-- Sección 4: Delito y Participantes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Delito*</label>
            <input type="text" wire:model="delito" placeholder="Descripción del delito" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            @error('delito') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">N° Testigos</label>
                <input type="number" wire:model="num_testigos" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">N° Peritos</label>
                <input type="number" wire:model="num_peritos" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </div>

        <!-- Sección 5: Encargados -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado de Causa*</label>
                <input type="text" wire:model="encargado_causa" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('encargado_causa') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado TTP*</label>
                <input type="text" wire:model="encargado_ttp" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                @error('encargado_ttp') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado TTP Zoom</label>
                <input type="text" wire:model="encargado_ttp_zoom" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </div>

        <!-- Sección 6: Estado -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select wire:model="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="POR_REALIZARSE">Por Realizarse</option>
                <option value="EN_CURSO">En Curso</option>
                <option value="RECESO">Receso</option>
                <option value="FINALIZADA">Finalizada</option>
            </select>
        </div>

        <!-- Sección 7: Magistrados -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Jueces inhabilitados*</h3>
            
            @error('jueces_inhabilitados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            
            <div class="space-y-4">
                <!-- Formulario para nuevo magistrado -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Agregar Juez</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="flex-grow">
                            <input 
                                type="text" 
                                wire:model="Nuevosjueces_inhabilitados.nombre_completo" 
                                placeholder="Nombre completo del magistrado" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                            >
                            @error('Nuevosjueces_inhabilitados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <button 
                                type="button" 
                                wire:click="Agregarjueces_inhabilitados" 
                                class="bg-blue-500 text-black px-4 py-2 rounded-md text-sm hover:bg-blue-600"
                            >
                                Agregar Juez
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Lista de jueces -->
                @if(count($jueces_inhabilitados) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($jueces_inhabilitados as $index => $jueces_inhabilitados)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $jueces_inhabilitados['nombre_completo'] }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <button 
                                                type="button" 
                                                wire:click="eliminarjueces_inhabilitados({{ $index }})" 
                                                class="text-red-500 hover:text-red-700 text-sm"
                                            >
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">No hay Jueces agregados</p>
                @endif
            </div>
        </div>

        <!-- Sección 8: Acusados -->

        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Acusados*</h3>
            
            @error('acusados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            
            <div class="space-y-4">
                <!-- Formulario para nuevo acusado -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Agregar Acusado</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <input type="text" wire:model="nuevoAcusado.nombre_completo" placeholder="Nombre completo" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            @error('nuevoAcusado.nombre_completo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        
                        <div>
                            <input type="text" wire:model="nuevoAcusado.situacion" list="tiposdelibertad" placeholder="Situacion" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <datalist id="tiposdelibertad">
                                @foreach($tiposdelibertad as $tipoL)
                                    <option value="{{ $tipoL }}">
                                @endforeach
                            </datalist>
                        </div>
                        
                        <div>
                            <input type="text" wire:model="nuevoAcusado.medida_cautelar" placeholder="Medidas cautelares" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                             
                        </div>
                        
                        <div>
                            <input type="text" wire:model="nuevoAcusado.forma_notificacion" placeholder="Forma notificación" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                             
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="button" wire:click="agregarAcusado" class="w-full bg-blue-500 text-black px-3 py-2 rounded-md text-sm hover:bg-blue-600">
                            Agregar Acusado
                        </button>
                    </div>
                </div>
                
                <!-- Lista de acusados -->
                @if(is_array($acusados) && count($acusados) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Situación</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Medidas</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Notificación</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($acusados as $index => $acusado)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $acusado['nombre_completo'] }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full 
                                                {{ $acusado['situacion'] == 'LIBRE' ? 'bg-green-100 text-green-800' : 
                                                   ($acusado['situacion'] == 'DETENIDO' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                                {{ $acusado['situacion'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">{{ $acusado['medida_cautelar'] }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $acusado['forma_notificacion'] }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <button type="button" wire:click="eliminarAcusado({{ $index }})" class="text-red-500 hover:text-red-700 text-sm">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500 italic">No hay acusados agregados</p>
                @endif
            </div>
        </div>
        @if(session('success'))
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg">
                {{ session('success') }}
            </div>
        @endif
        <!-- Botón de enviar -->
        <div class="flex justify-end pt-6">
            <button type="submit" class="bg-black-100 text-black px-6 py-2 rounded-md hover:bg-green-600">
                {{ $audienciaId ? 'Actualizar' : 'Guardar' }} Audiencia
            </button>
        </div>
    </form>
</div>