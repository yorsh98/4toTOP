<div>
    @php
        // Helper para mostrar "vacío" en letra muy chica cuando el valor venga null o vacío
        $fmt = function ($v) {
            if (is_array($v)) {
                if (count($v) === 0) return '<span class="text-[10px] text-gray-400 italic">vacío</span>';
                // para arrays simples mostramos JSON legible
                return e(json_encode($v, JSON_UNESCAPED_UNICODE));
            }
            if ($v === null || $v === '') {
                return '<span class="text-[10px] text-gray-400 italic">vacío</span>';
            }
            return e($v);
        };
    @endphp

    <!-- Filtro superior -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-3 mb-4">
        <div>
            <label class="block text-sm text-gray-600 mb-1">Fecha</label>
            <input type="date" wire:model.lazy="fecha"
                   class="px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div class="flex items-center gap-2">
            <button type="button" wire:click="diaAnterior"
                    class="px-3 py-2 border rounded-md hover:bg-gray-50">← Día anterior</button>
            <button type="button" wire:click="hoy"
                    class="px-3 py-2 border rounded-md hover:bg-gray-50">Hoy</button>
            <button type="button" wire:click="diaSiguiente"
                    class="px-3 py-2 border rounded-md hover:bg-gray-50">Día siguiente →</button>
        </div>
    </div>

    <div class="mb-3 text-sm text-gray-600">
        Mostrando audiencias del <span class="font-semibold">{{ \Carbon\Carbon::parse($fecha)->format('d-m-Y') }}</span>.
        Total: <span class="font-semibold">{{ count($audiencias) }}</span>
    </div>

    @if (count($audiencias) === 0)
        <div class="rounded-md border bg-amber-50 p-4 text-sm text-amber-800">
            No se encontraron audiencias para la fecha seleccionada.
        </div>
    @else
        <div class="overflow-x-auto border rounded-md">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-gray-600">
                        <th class="px-3 py-2">Hora</th>
                        <th class="px-3 py-2">Sala</th>
                        <th class="px-3 py-2">Ubicación</th>
                        <th class="px-3 py-2">Tipo</th>
                        <th class="px-3 py-2">RIT</th>
                        <th class="px-3 py-2">RUC</th>
                        <th class="px-3 py-2">Encargado sala</th>
                        <th class="px-3 py-2">Acta</th>
                        <th class="px-3 py-2">JuezP</th>
                        <th class="px-3 py-2">JuezR</th>
                        <th class="px-3 py-2">JuezI</th>
                        <th class="px-3 py-2">Anfitrión</th>
                        <th class="px-3 py-2">Cuenta Zoom</th>
                        <th class="px-3 py-2">Estado</th>
                        <th class="px-3 py-2">#Testigos</th>
                        <th class="px-3 py-2">#Peritos</th>
                        <th class="px-3 py-2">Duración</th>
                        <th class="px-3 py-2">Delito</th>
                        <th class="px-3 py-2">Jueces inhabilitados</th>
                        <th class="px-3 py-2">Acusados</th>
                        <th class="px-3 py-2 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach ($audiencias as $aud)
                        <tr>
                            <td class="px-3 py-2">{!! $fmt($aud->hora_inicio) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->sala) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->ubicacion) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->tipo_audiencia) !!}</td>
                            <td class="px-3 py-2 font-medium">{!! $fmt($aud->rit) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->ruc) !!}</td>

                            {{-- Encargado de sala: si lo guardas en 'encargado_sala'; si no, usamos 'encargado_causa' --}}
                            <td class="px-3 py-2">
                                {!! $fmt($aud->encargado_sala ?? $aud->encargado_causa) !!}
                            </td>

                            <td class="px-3 py-2">{!! $fmt($aud->acta) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->JuezP) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->JuezR) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->JuezI) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->anfitrion) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->cta_zoom) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->estado) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->num_testigos) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->num_peritos) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->duracion) !!}</td>
                            <td class="px-3 py-2">{!! $fmt($aud->delito) !!}</td>

                            <td class="px-3 py-2">
                                @php
                                    $inh = $aud->jueces_inhabilitados ?? [];
                                @endphp
                                @if (empty($inh))
                                    <span class="text-[10px] text-gray-400 italic">vacío</span>
                                @else
                                    <div class="max-w-[220px] break-words">
                                        {{ implode(', ', $inh) }}
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2">
                                @if (empty($aud->acusados))
                                    <span class="text-[10px] text-gray-400 italic">vacío</span>
                                @else
                                    <div class="max-w-[260px] break-words">
                                        <ul class="list-disc pl-4 space-y-1">
                                            @foreach ($aud->acusados as $ac)
                                                <li>
                                                    <span class="font-medium">{{ $ac['nombre_completo'] ?? '—' }}</span>
                                                    @if (!empty($ac['situacion'])) <span class="text-gray-500"> · {{ $ac['situacion'] }}</span> @endif
                                                    @if (!empty($ac['medida_cautelar'])) <span class="text-gray-500"> · {{ $ac['medida_cautelar'] }}</span> @endif
                                                    @if (!empty($ac['forma_notificacion'])) <span class="text-gray-500"> · {{ $ac['forma_notificacion'] }}</span> @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </td>

                            <td class="px-3 py-2 text-right">
                                <button
                                    type="button"
                                    class="text-red-600 hover:underline"
                                    x-on:click="
                                        if (confirm('¿Eliminar esta audiencia (RIT {{ $aud->rit }})?')) {
                                            $wire.eliminar({{ $aud->id }});
                                        }
                                    ">
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @script
    <script>
        document.addEventListener('audiencia-eliminada', () => {            
            Swal?.fire?.({ icon:'success', title:'Eliminada', timer:1200, showConfirmButton:false });                        
        });
    </script>
    @endscript
</div>
