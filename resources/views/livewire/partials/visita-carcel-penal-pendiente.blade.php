@if ($imputado['penal_manual'] ?? blank($imputado['penal']))
    @php
        $referencia = $tipo.'.'.$indice.'.'.$subindice;
        $idSelector = 'penal-pendiente-'.str_replace('.', '-', $referencia);
    @endphp
    <div wire:key="{{ $idSelector }}-{{ $imputado['ruc'] }}-{{ $imputado['rut'] }}" class="mt-3 rounded-lg border border-amber-200 bg-amber-50/60 p-3">
        <label for="{{ $idSelector }}" class="mb-1.5 block text-xs font-semibold text-slate-800">Asignar penal de reclusión</label>
        <select id="{{ $idSelector }}"
            wire:change="asignarPenalPendiente('{{ $tipo }}', {{ $indice }}, {{ $subindice }}, $event.target.value)"
            wire:loading.attr="disabled" wire:target="asignarPenalPendiente"
            aria-describedby="{{ $idSelector }}-estado"
            class="w-full rounded-lg border-slate-300 bg-white py-2.5 pl-3 pr-9 text-sm text-slate-800 focus:border-[#0A2E5D] focus:ring-[#0A2E5D] disabled:opacity-60">
            <option value="" disabled @selected(! $imputado['penal'])>Seleccione un penal</option>
            @foreach ($penales as $penalActivo)
                <option value="{{ $penalActivo->id }}" @selected($imputado['penal'] === $penalActivo->nombre)>{{ $penalActivo->nombre }}</option>
            @endforeach
        </select>
        <div id="{{ $idSelector }}-estado" class="mt-2 text-xs" role="status">
            @error('penalPendiente.'.$referencia)
                <p class="font-medium text-red-700">{{ $message }}</p>
            @else
                @if ($imputado['valido'])
                    <p class="font-medium text-emerald-700">Penal asignado. Listo para ingresar.</p>
                @elseif (! $imputado['penal'])
                    <p class="text-amber-800">Selecciona un penal activo para completar este antecedente.</p>
                @endif
            @enderror
        </div>
    </div>
@endif
