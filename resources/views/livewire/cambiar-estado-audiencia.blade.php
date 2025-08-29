<div class="space-y-2">
    <!-- Botones de filtro -->
    <div class="flex flex-wrap gap-2">        
        @foreach($tiposAudiencia as $tipo)
            <button 
                wire:click="setFiltro('{{ $tipo }}')"
                class="px-3 py-1 rounded-lg text-sm hover:shadow
                       {{ $filtro === $tipo ? 'bg-blue-600 text-white' : 'bg-gray-200 dark:bg-zinc-700 dark:text-gray-200' }}">
                {{ $tipo }}
            </button>
        @endforeach
    </div>
    <!-- Lista de audiencias -->
    @forelse($audiencias as $audiencia)
        <div class="grid grid-cols-3 gap-4 items-center p-3 bg-white shadow rounded-lg dark:bg-zinc-900">
            <!-- RIT -->
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                RIT: {{ $audiencia->rit }}
            </div>
            <!-- Sala -->
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Sala: {{ $audiencia->sala }}
            </div>
            <!-- Estado -->
            <div wire:key="estado-{{ $audiencia->id }}-{{ $audiencia->updated_at->timestamp }}">
                <select 
                    wire:change="cambiarEstado({{ $audiencia->id }}, $event.target.value)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-zinc-800 dark:text-gray-200">
                    <option value="POR_REALIZARSE" @selected($audiencia->estado === 'POR_REALIZARSE')>POR REALIZARSE</option>
                    <option value="EN_CURSO" @selected($audiencia->estado === 'EN_CURSO')>EN CURSO</option>
                    <option value="RECESO" @selected($audiencia->estado === 'RECESO')>RECESO</option>
                    <option value="FINALIZADA" @selected($audiencia->estado === 'FINALIZADA')>FINALIZADA</option>
                </select>
            </div>
        </div>
    @empty
        <div class="text-center text-gray-500 dark:text-gray-400">
            @if(!$filtro)
                Selecciona un tipo de audiencia para comenzar.
            @else
                No hay audiencias de este tipo hoy.
            @endif
        </div>
    @endforelse
</div>
