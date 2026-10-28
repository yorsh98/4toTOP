<div class="fo-estado">
    <div class="fo-chips" role="group" aria-label="Tipo de audiencia">
        @foreach($tiposAudiencia as $tipo)
            <button type="button"
                    wire:click="setFiltro('{{ $tipo }}')"
                    class="fo-chip cm-press"
                    aria-pressed="{{ $filtro === $tipo ? 'true' : 'false' }}">
                {{ $tipo }}
            </button>
        @endforeach
    </div>

    <p class="fo-estado-loading cm-shimmer" wire:loading wire:target="setFiltro">Cargando audiencias…</p>

    <ul class="fo-estado-list" wire:loading.remove wire:target="setFiltro">
        @forelse($audiencias as $audiencia)
            <li class="fo-estado-row cm-rise" style="animation-delay: {{ $loop->index * 50 }}ms">
                <span class="fo-num fo-estado-rit">RIT {{ $audiencia->rit }}</span>
                <span class="fo-estado-sala">Sala {{ $audiencia->sala }}</span>
                <span wire:key="estado-{{ $audiencia->id }}-{{ $audiencia->updated_at->timestamp }}">
                    @auth
                        <label class="sr-only" for="estado-{{ $audiencia->id }}">Estado de RIT {{ $audiencia->rit }}</label>
                        <select id="estado-{{ $audiencia->id }}"
                                wire:change="cambiarEstado({{ $audiencia->id }}, $event.target.value)"
                                class="fo-select fo-select--sm">
                            <option value="POR_REALIZARSE" @selected($audiencia->estado === 'POR_REALIZARSE')>Por realizarse</option>
                            <option value="EN_CURSO" @selected($audiencia->estado === 'EN_CURSO')>En curso</option>
                            <option value="RECESO" @selected($audiencia->estado === 'RECESO')>Receso</option>
                            <option value="FINALIZADA" @selected($audiencia->estado === 'FINALIZADA')>Finalizada</option>
                        </select>
                    @else
                        <span class="fo-status" data-estado="{{ $audiencia->estado }}">
                            {{ ucfirst(mb_strtolower(str_replace('_', ' ', $audiencia->estado))) }}
                        </span>
                    @endauth
                </span>
            </li>
        @empty
            <li class="fo-muted fo-estado-empty">
                @if(!$filtro)
                    Elige un tipo de audiencia para ver las de hoy.
                @else
                    Hoy no hay audiencias de tipo «{{ $filtro }}».
                @endif
            </li>
        @endforelse
    </ul>

    @if($filtro)
        <p class="fo-estado-foot">Actualizado a las <span class="fo-num">{{ $ultimaActualizacion }}</span></p>
    @endif
</div>
