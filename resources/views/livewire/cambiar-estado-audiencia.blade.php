<div class="space-y-2">
    <!-- Indicador de última actualización -->
    <div class="text-xs text-right text-gray-500">
        Actualizado: {{ $ultimaActualizacion }}
    </div>

    @foreach($audiencias as $audiencia)
        <div class="grid grid-cols-3 gap-4 items-center p-4 bg-white shadow rounded-lg dark:bg-zinc-900">
            <!-- RIT -->
            <div class="text-sm font-semibold text-gray-800 dark:text-gray-200">
                RIT: {{ $audiencia->rit }}
            </div>

            <!-- Sala -->
            <div class="text-sm text-gray-600 dark:text-gray-400">
                Sala: {{ $audiencia->sala }}
            </div>

            <!-- Estado - Key importante para forzar actualización -->
            <div wire:key="estado-{{ $audiencia->id }}-{{ $audiencia->updated_at->timestamp }}">
                <select 
                    wire:change="cambiarEstado({{ $audiencia->id }}, $event.target.value)"
                    class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-zinc-800 dark:text-gray-200"
                >
                    <option value="POR_REALIZARSE" @selected($audiencia->estado === 'POR_REALIZARSE')>POR REALIZARSE</option>
                    <option value="EN_CURSO" @selected($audiencia->estado === 'EN_CURSO')>EN CURSO</option>
                    <option value="RECESO" @selected($audiencia->estado === 'RECESO')>RECESO</option>
                    <option value="FINALIZADA" @selected($audiencia->estado === 'FINALIZADA')>FINALIZADA</option>
                </select>
            </div>
        </div>
    @endforeach
</div>