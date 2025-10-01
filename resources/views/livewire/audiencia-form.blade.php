@php
  $baseInput = "w-full px-3 py-2 border rounded-md border-gray-300
                focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                invalid:border-red-500 invalid:focus:border-red-500 invalid:focus:ring-red-300
                scroll-mt-24";
@endphp

<div class="max-w-4xl mx-auto p-6 bg-blue-50 rounded border">
    <h2 class="text-2xl font-bold text-gray-800 mb-6">
        {{ $audienciaId ? 'Editar' : 'Registro de Nueva' }} Audiencia
    </h2>
    <form x-data
  x-on:submit.prevent="
    const f = $el;
    const firstInvalid = f.querySelector(':invalid');
    if (firstInvalid) {
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      firstInvalid.focus({ preventScroll: true });
      firstInvalid.reportValidity();
      return;
    }
    $wire.guardarAudiencia();" class="space-y-6">
        <!-- Sección 1: Información Básica -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RIT*</label>
                <input 
                    type="text" 
                    wire:model.defer="rit" required
                    wire:input.debounce.600ms="buscarPorRit" 
                    placeholder="Ej: 123-2025" 
                    class="{{ $baseInput }} {{ $errors->has('rit') ? 'border-red-500 ring-red-300' : '' }}"
                    aria-invalid="{{ $errors->has('rit') ? 'true' : 'false' }}">
                @error('rit') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Duración*</label>
                <input type="text" wire:model.defer="duracion" required placeholder="Ej: 2 horas, 1 día" class="{{ $baseInput }} {{ $errors->has('duracion') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('duracion') ? 'true' : 'false' }}">
                @error('duracion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div> 
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha*</label>
                <input type="date" wire:model.defer="fecha" required class="{{ $baseInput }} {{ $errors->has('fecha') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('fecha') ? 'true' : 'false' }}">
                @error('fecha') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">RUC</label>
                <input type="text" wire:model.defer="ruc" required placeholder="Ej: 0123456789-1" class="{{ $baseInput }} {{ $errors->has('ruc') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('ruc') ? 'true' : 'false' }}">
                @error('ruc') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror            
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sala*</label>
                <input type="text" wire:model.defer="sala" required placeholder="Ej: Sala 701" class="{{ $baseInput }} {{ $errors->has('sala') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('sala') ? 'true' : 'false' }}">
                @error('sala') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Ubicación*</label>
                <input type="text" wire:model.defer="ubicacion" required placeholder="Ej: Edificio A, Piso 7" class="{{ $baseInput }} {{ $errors->has('ubicacion') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('ubicacion') ? 'true' : 'false' }}">
                @error('ubicacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <!-- Sección 2: Ubicación, Horario y datos complementarios -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">                        
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hora Inicio*</label>
                <input type="time" wire:model.defer="hora_inicio" required class="{{ $baseInput }} {{ $errors->has('hora_inicio') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('hora_inicio') ? 'true' : 'false' }}">
                @error('hora_inicio') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Audiencia*</label>
                <input type="text" wire:model.defer="tipo_audiencia" required list="tiposAudiencia" placeholder="Seleccione o escriba" class="{{ $baseInput }} {{ $errors->has('tipo_audiencia') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('tipo_audiencia') ? 'true' : 'false' }}">
                <datalist id="tiposAudiencia">
                    @foreach($tiposAudiencia as $tipo)
                        <option value="{{ $tipo }}">
                    @endforeach
                </datalist>
                @error('tipo_audiencia') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cuenta Zoom</label>
                <input type="text" wire:model.defer="cta_zoom" required placeholder="ID de reunión Zoom o VC" class="{{ $baseInput }} {{ $errors->has('cta_zoom') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('cta_zoom') ? 'true' : 'false' }}">
                @error('cta_zoom') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
       <hr> 
        <!-- Sección 3: Delito y Participantes -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Delito*</label>
            <input type="text" wire:model.defer="delito" required placeholder="Descripción del delito" class="{{ $baseInput }} {{ $errors->has('delito') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('delito') ? 'true' : 'false' }}">
            @error('delito') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">N° Testigos</label>
                <input type="number" wire:model.defer="num_testigos" required min="0" class="{{ $baseInput }} {{ $errors->has('num_testigos') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('num_testigos') ? 'true' : 'false' }}">
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">N° Peritos</label>
                <input type="number" wire:model.defer="num_peritos" required min="0" class="{{ $baseInput }} {{ $errors->has('num_peritos') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('num_peritos') ? 'true' : 'false' }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado de Acta*</label>
                <input type="text" wire:model.defer="acta" required min="0" class="{{ $baseInput }} {{ $errors->has('acta') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('acta') ? 'true' : 'false' }}">
                @error('acta') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <!-- Sección 4: Encargados -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado de Causa*</label>
                <input type="text" wire:model.defer="encargado_causa" required class="{{ $baseInput }} {{ $errors->has('encargado_causa') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('encargado_causa') ? 'true' : 'false' }}">
                @error('encargado_causa') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado TTP*</label>
                <input type="text" wire:model.defer="encargado_ttp" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Encargado TTP Zoom</label>
                <input type="text" wire:model.defer="encargado_ttp_zoom" class="w-full px-3 py-2 border border-gray-300 rounded-md">
            </div>
        </div>
        <!-- Sección 5: Magistrados -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Juez Presidente*</label>
                <input type="text" wire:model.defer="JuezP" required class="{{ $baseInput }} {{ $errors->has('JuezP') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('JuezP') ? 'true' : 'false' }}">
                @error('JuezP') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Juez Redactor*</label>
                <input type="text" wire:model.defer="JuezR" required class="{{ $baseInput }} {{ $errors->has('JuezR') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('JuezR') ? 'true' : 'false' }}">
                @error('JuezR') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Juez Integrante*</label>
                <input type="text" wire:model.defer="JuezI" required class="{{ $baseInput }} {{ $errors->has('JuezI') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('JuezI') ? 'true' : 'false' }}">
                @error('JuezI') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Jueces inhabilitados*</h3>            
            @error('jueces_inhabilitados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror            
            <div class="space-y-4">
                <!-- Formulario para nuevo magistrado -->
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Agregar Juez</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="flex-grow">
                            <input 
                                type="text" 
                                wire:model.defer="Nuevosjueces_inhabilitados.nombre_completo" 
                                placeholder="Nombre completo del magistrado"
                                class="{{ $baseInput }} {{ $errors->has('Nuevosjueces_inhabilitados') ? 'border-red-500 ring-red-300' : '' }}" aria-invalid="{{ $errors->has('Nuevosjueces_inhabilitados') ? 'true' : 'false' }}">
                            @error('Nuevosjueces_inhabilitados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <button 
                                type="button" 
                                wire:click="Agregarjueces_inhabilitados" 
                                class="bg-blue-200 text-black px-4 py-2 rounded-md text-sm hover:bg-blue-600">
                                + Agregar
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
                                                class="text-red-500 hover:text-red-700 text-sm">
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
        <!-- Sección 6: Acusados -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium text-gray-800 mb-4">Acusados*</h3>            
            @error('acusados') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror            
            <div class="space-y-4">
                <!-- Formulario para nuevo acusado -->
                <div class="bg-gray-50 p-4 rounded-lg border">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Agregar Acusado</h4>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.nombre_completo" placeholder="Nombre completo" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            @error('nuevoAcusado.nombre_completo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>                        
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.situacion" list="tiposdelibertad" placeholder="Situacion" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <datalist id="tiposdelibertad">
                                @foreach($tiposdelibertad as $tipoL)
                                    <option value="{{ $tipoL }}">
                                @endforeach
                            </datalist>
                             @error('nuevoAcusado.situacion') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>                        
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.medida_cautelar" placeholder="Medidas cautelares" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">                             
                        </div>                        
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.forma_notificacion" placeholder="Forma notificación" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">                             
                        </div>
                        <button type="button" wire:click="agregarAcusado" class="w-full bg-blue-200 text-black px-3 py-2 rounded-md text-sm hover:bg-blue-600">
                            + Agregar
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
        <!-- Sección 6: Estado -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
            <select wire:model.defer="estado" class="w-full px-3 py-2 border border-gray-300 rounded-md">
                <option value="POR_REALIZARSE">Por Realizarse</option>
                <option value="EN_CURSO">En Curso</option>
                <option value="RECESO">Receso</option>
                <option value="FINALIZADA">Finalizada</option>
            </select>
        </div>
        <!-- Botón de enviar -->
        <div class="flex justify-end pt-6">
            <button type="submit" class="bg-green-300 text-black px-6 py-2 rounded-md hover:bg-green-600">
                {{ $audienciaId ? 'Actualizar' : 'Guardar' }} Audiencia
            </button>
        </div>
    </form>
</div>
@script
<script>
    document.addEventListener('alerta-success', () => {
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '¡Audiencia agregada o actualizada correctamente!',
            confirmButtonColor: '#3085d6'
        });
    });
</script>        
@endscript