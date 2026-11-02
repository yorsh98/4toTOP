

<div class="max-w-4xl mx-auto p-6 bo-panel rounded border">
    <x-flash-notifications />
    <h2 class="text-2xl font-bold bo-strong mb-6 bo-cat-heading bo-cat-juicio">
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
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">RIT*</label>
                <input
                    type="text"
                    wire:model.defer="rit" required
                    wire:input.debounce.600ms="buscarPorRit"
                    placeholder="Ej: 123-2025"
                    class="bo-input w-full {{ $errors->has('rit') ? ' ' : '' }}"
                    aria-invalid="{{ $errors->has('rit') ? 'true' : 'false' }}">
                @error('rit') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Duración*</label>
                <input type="text" wire:model.defer="duracion" required list="tiposduracion" placeholder="Ej: 2 horas, 1 día" class="bo-input w-full {{ $errors->has('duracion') ? ' ' : '' }}" aria-invalid="{{ $errors->has('duracion') ? 'true' : 'false' }}">
                <datalist id="tiposduracion">
                    @foreach($tiposduracion as $tipo)
                        <option value="{{ $tipo }}">
                    @endforeach
                </datalist>
                @error('duracion') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Fecha*</label>
                <input type="date" wire:model.defer="fecha" required class="bo-input w-full {{ $errors->has('fecha') ? ' ' : '' }}" aria-invalid="{{ $errors->has('fecha') ? 'true' : 'false' }}">
                @error('fecha') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">RUC</label>
                <input type="text" wire:model.defer="ruc" required placeholder="Ej: 0123456789-1" class="bo-input w-full {{ $errors->has('ruc') ? ' ' : '' }}" aria-invalid="{{ $errors->has('ruc') ? 'true' : 'false' }}">
                @error('ruc') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Sala*</label>
                <input type="text" wire:model.defer="sala" required placeholder="Ej: Sala 701" class="bo-input w-full {{ $errors->has('sala') ? ' ' : '' }}" aria-invalid="{{ $errors->has('sala') ? 'true' : 'false' }}">
                @error('sala') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Ubicación*</label>
                <input type="text" wire:model.defer="ubicacion" required placeholder="Ej: Edificio A, Piso 7" class="bo-input w-full {{ $errors->has('ubicacion') ? ' ' : '' }}" aria-invalid="{{ $errors->has('ubicacion') ? 'true' : 'false' }}">
                @error('ubicacion') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <!-- Sección 2: Ubicación, Horario y datos complementarios -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Hora Inicio*</label>
                <input type="time" wire:model.defer="hora_inicio" required class="bo-input w-full {{ $errors->has('hora_inicio') ? ' ' : '' }}" aria-invalid="{{ $errors->has('hora_inicio') ? 'true' : 'false' }}">
                @error('hora_inicio') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Tipo Audiencia*</label>
                <input type="text" wire:model.defer="tipo_audiencia" required list="tiposAudiencia" placeholder="Seleccione o escriba" class="bo-input w-full {{ $errors->has('tipo_audiencia') ? ' ' : '' }}" aria-invalid="{{ $errors->has('tipo_audiencia') ? 'true' : 'false' }}">
                <datalist id="tiposAudiencia">
                    @foreach($tiposAudiencia as $tipo)
                        <option value="{{ $tipo }}">
                    @endforeach
                </datalist>
                @error('tipo_audiencia') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Cuenta Zoom</label>
                <input type="text" wire:model.defer="cta_zoom" required placeholder="ID de reunión Zoom o VC" class="bo-input w-full {{ $errors->has('cta_zoom') ? ' ' : '' }}" aria-invalid="{{ $errors->has('cta_zoom') ? 'true' : 'false' }}">
                @error('cta_zoom') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
       <hr>
        <!-- Sección 3: Delito y Participantes -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-2 gap-6">
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Delito*</label>
                <input type="text" wire:model.defer="delito" required placeholder="Descripción del delito" class="bo-input w-full {{ $errors->has('delito') ? ' ' : '' }}" aria-invalid="{{ $errors->has('delito') ? 'true' : 'false' }}">
                @error('delito') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Observación</label>
                <input type="text" wire:model.defer="obs" class="bo-input w-full {{ $errors->has('obs') ? ' ' : '' }}" aria-invalid="{{ $errors->has('obs') ? 'true' : 'false' }}">
            </div>
        </div>


        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">N° Testigos</label>
                <input type="number" wire:model.defer="num_testigos" required min="0" class="bo-input w-full {{ $errors->has('num_testigos') ? ' ' : '' }}" aria-invalid="{{ $errors->has('num_testigos') ? 'true' : 'false' }}">
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">N° Peritos</label>
                <input type="number" wire:model.defer="num_peritos" required min="0" class="bo-input w-full {{ $errors->has('num_peritos') ? ' ' : '' }}" aria-invalid="{{ $errors->has('num_peritos') ? 'true' : 'false' }}">
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Encargado de Acta*</label>
                <input type="text" wire:model.defer="acta" required list="actaAnfitrionList" class="bo-input w-full {{ $errors->has('acta') ? ' ' : '' }}" aria-invalid="{{ $errors->has('acta') ? 'true' : 'false' }}">
                @error('acta') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
        </div>
        <!-- Sección 4: Encargados -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Encargado de Causa*</label>
                <input type="text" wire:model.defer="encargado_causa" required list="encargadoCausaList" class="bo-input w-full {{ $errors->has('encargado_causa') ? ' ' : '' }}" aria-invalid="{{ $errors->has('encargado_causa') ? 'true' : 'false' }}">
                @error('encargado_causa') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Encargado TTP*</label>
                <input type="text" wire:model.defer="encargado_ttp" list="ttyppList" class="bo-input w-full px-3 py-2 border bo-border rounded-md">
                @error('encargado_ttp') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Encargado TTP Zoom</label>
                <input type="text" wire:model.defer="encargado_ttp_zoom" list="ttyppList" class="bo-input w-full px-3 py-2 border bo-border rounded-md">
                @error('encargado_ttp_zoom') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Anfitrion</label>
                <input type="text" wire:model.defer="anfitrion" list="actaAnfitrionList" class="bo-input w-full px-3 py-2 border bo-border rounded-md">
                @error('anfitrion') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <datalist id="encargadoCausaList">
                @foreach($encargadoCausaOptions as $nombre)
                    <option value="{{ $nombre }}">
                @endforeach
            </datalist>
            <datalist id="ttyppList">
                @foreach($ttyppOptions as $nombre)
                    <option value="{{ $nombre }}">
                @endforeach
            </datalist>
            <datalist id="actaAnfitrionList">
                @foreach($actaAnfitrionOptions as $nombre)
                    <option value="{{ $nombre }}">
                @endforeach
            </datalist>
        </div>
        <!-- Sección 5: Magistrados -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Presidente*</label>
                <input type="text" wire:model.defer="JuezP" required list="juecesList" class="bo-input w-full {{ $errors->has('JuezP') ? ' ' : '' }}" aria-invalid="{{ $errors->has('JuezP') ? 'true' : 'false' }}">
                @error('JuezP') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Redactor*</label>
                <input type="text" wire:model.defer="JuezR" required list="juecesList" class="bo-input w-full {{ $errors->has('JuezR') ? ' ' : '' }}" aria-invalid="{{ $errors->has('JuezR') ? 'true' : 'false' }}">
                @error('JuezR') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="bo-label block text-sm font-medium bo-strong mb-1">Juez Integrante*</label>
                <input type="text" wire:model.defer="JuezI" required list="juecesList" class="bo-input w-full {{ $errors->has('JuezI') ? ' ' : '' }}" aria-invalid="{{ $errors->has('JuezI') ? 'true' : 'false' }}">
                @error('JuezI') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            </div>
            <datalist id="juecesList">
                @foreach($juecesOptions as $nombre)
                    <option value="{{ $nombre }}">
                @endforeach
            </datalist>
        </div>
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium bo-strong mb-4">Jueces inhabilitados*</h3>
            @error('jueces_inhabilitados') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            <div class="space-y-4">
                <!-- Formulario para nuevo magistrado -->
                <div class="bo-panel-subtle p-4 rounded-lg border">
                    <h4 class="text-sm font-medium bo-strong mb-3">Agregar Juez</h4>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="flex-grow">
                            <input
                                type="text"
                                wire:model.defer="Nuevosjueces_inhabilitados.nombre_completo"
                                list="juecesInhabilitadosOpciones"
                                placeholder="Nombre completo del magistrado"
                                class="bo-input w-full {{ $errors->has('Nuevosjueces_inhabilitados') ? ' ' : '' }}" aria-invalid="{{ $errors->has('Nuevosjueces_inhabilitados') ? 'true' : 'false' }}">
                            <datalist id="juecesInhabilitadosOpciones">
                                @foreach($juecesOptions as $nombre)
                                    <option value="{{ $nombre }}">
                                @endforeach
                            </datalist>
                            @error('Nuevosjueces_inhabilitados') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <button
                                type="button"
                                wire:click="Agregarjueces_inhabilitados"
                                class="bo-btn bo-btn--secondary  bo-strong px-4 py-2 rounded-md text-sm ">
                                + Agregar
                            </button>
                        </div>
                    </div>
                </div>
                <!-- Lista de jueces -->
                @if(count($jueces_inhabilitados) > 0)
                    <div class="overflow-x-auto">
                        <table class="bo-table bo-audiencias-table text-sm">
                            <thead class="bo-panel-subtle">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bo-panel divide-y bo-border">
                                @foreach($jueces_inhabilitados as $index => $jueces_inhabilitados)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $jueces_inhabilitados['nombre_completo'] }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <button
                                                type="button"
                                                wire:click="eliminarjueces_inhabilitados({{ $index }})"
                                                class="bo-btn bo-btn--secondary bo-text--danger text-sm">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm bo-muted italic">No hay Jueces agregados</p>
                @endif
            </div>
        </div>
        <!-- Sección 6: Acusados -->
        <div class="border-t pt-6">
            <h3 class="text-lg font-medium bo-strong mb-4">Acusados*</h3>
            @error('acusados') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
            <div class="space-y-4">
                <!-- Formulario para nuevo acusado -->
                <div class="bo-panel-subtle p-4 rounded-lg border">
                    <h4 class="text-sm font-medium bo-strong mb-3">Agregar Acusado</h4>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.nombre_completo" placeholder="Nombre completo" class="bo-input w-full px-3 py-2 border bo-border rounded-md text-sm">
                            @error('nuevoAcusado.nombre_completo') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.situacion" list="tiposdelibertad" placeholder="Situacion" class="bo-input w-full px-3 py-2 border bo-border rounded-md text-sm">
                            <datalist id="tiposdelibertad">
                                @foreach($tiposdelibertad as $tipoL)
                                    <option value="{{ $tipoL }}">
                                @endforeach
                            </datalist>
                             @error('nuevoAcusado.situacion') <span class="bo-text--danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.medida_cautelar" placeholder="Medidas cautelares" class="bo-input w-full px-3 py-2 border bo-border rounded-md text-sm">
                        </div>
                        <div>
                            <input type="text" wire:model.defer="nuevoAcusado.forma_notificacion" placeholder="Forma notificación" class="bo-input w-full px-3 py-2 border bo-border rounded-md text-sm">
                        </div>
                        <button type="button" wire:click="agregarAcusado" class="bo-btn bo-btn--secondary w-full bo-strong px-3 py-2 rounded-md text-sm ">
                            + Agregar
                        </button>
                    </div>

                </div>
                <!-- Lista de acusados -->
                @if(is_array($acusados) && count($acusados) > 0)
                    <div class="overflow-x-auto">
                        <table class="bo-table bo-audiencias-table text-sm">
                            <thead class="bo-panel-subtle">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Nombre</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Situación</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Medidas</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Notificación</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium bo-muted uppercase">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bo-panel divide-y bo-border">
                                @foreach($acusados as $index => $acusado)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $acusado['nombre_completo'] }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <span class="px-2 py-1 text-xs rounded-full
 {{ $acusado['situacion'] == 'LIBRE' ? ' ' :
 ($acusado['situacion'] == 'DETENIDO' ? ' ' : 'bo-chip bo-chip--warn') }}">
                                                {{ $acusado['situacion'] }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-sm">{{ $acusado['medida_cautelar'] }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $acusado['forma_notificacion'] }}</td>
                                        <td class="px-4 py-2 text-sm">
                                            <button type="button" wire:click="eliminarAcusado({{ $index }})" class="bo-btn bo-btn--secondary bo-text--danger text-sm">
                                                Eliminar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm bo-muted italic">No hay acusados agregados</p>
                @endif
            </div>
        </div>
        <!-- Sección 6: Estado -->
        <div>
            <label class="bo-label block text-sm font-medium bo-strong mb-1">Estado</label>
            <select wire:model.defer="estado" class="bo-select w-full px-3 py-2 border bo-border rounded-md">
                <option value="POR_REALIZARSE">Por Realizarse</option>
                <option value="EN_CURSO">En Curso</option>
                <option value="RECESO">Receso</option>
                <option value="FINALIZADA">Finalizada</option>
            </select>
        </div>
        <!-- Botón de enviar -->
        <div class="flex justify-end pt-6">
            <button type="submit" class="bo-btn bo-btn--primary px-6 py-2 rounded-md">
                {{ $audienciaId ? 'Actualizar' : 'Guardar' }} Audiencia
            </button>
        </div>
    </form>
</div>
@script
<script>
    document.addEventListener('alerta-success', () => {
        SwalToast.fire({ icon: 'success', title: 'Audiencia guardada correctamente.' });
    });
</script>
@endscript
