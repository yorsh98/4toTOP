{{-- resources/views/livewire/audiencias-diarias.blade.php --}}
<div x-data="{ open: @entangle('showSend').live, listModal:false }">
    @php
        // Helper para mostrar "vacío" en letra muy chica cuando el valor venga null o vacío
        $fmt = function ($v) {
            if (is_array($v)) {
                if (count($v) === 0) return '<span class="text-[10px] text-gray-400 italic">vacío</span>';
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
            <a
              href="{{ route('audiencias.export.diaria', ['fecha' => $fecha]) }}"
              target="_blank"
              class="inline-flex items-center justify-center rounded-md px-3 py-2
                      transition-transform duration-200 ease-out
                      motion-safe:hover:-translate-y-0.5 motion-safe:hover:scale-105
                      focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500"
              title="Exportar Excel"
              aria-label="Exportar Excel">
              <i class="fas fa-file-excel inline-block" style="color:#217346; font-size:32px;"></i>
            </a>

            <button type="button" wire:click="diaAnterior" class="px-3 py-2 border rounded-md hover:bg-gray-50">← Día anterior</button>
            <button type="button" wire:click="hoy"         class="px-3 py-2 border rounded-md hover:bg-gray-50">Hoy</button>
            <button type="button" wire:click="diaSiguiente" class="px-3 py-2 border rounded-md hover:bg-gray-50">Día siguiente →</button>

            {{-- Abre el slide-over (precarga lista por defecto y muestra panel) --}}
            <button type="button"
                    wire:click="openSend"
                    class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                Enviar por correo
            </button>
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
                        <th class="px-3 py-2">Observaciones</th>
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
                            <td class="px-3 py-2">{!! $fmt($aud->encargado_sala ?? $aud->encargado_causa) !!}</td>
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
                                    if (is_string($inh)) {
                                        $decoded = json_decode($inh, true);
                                        $inh = json_last_error() === JSON_ERROR_NONE ? $decoded : [$inh];
                                    }
                                    $labels = collect($inh)->map(function ($item) {
                                        if (is_array($item)) return $item['nombre_completo'] ?? null;
                                        return $item;
                                    })->filter()->values();
                                @endphp
                                @if ($labels->isEmpty())
                                    <span class="text-[10px] text-gray-400 italic">vacío</span>
                                @else
                                    <div class="max-w-[220px] break-words">{{ $labels->join(', ') }}</div>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="max-w-[280px] break-words">{!! $fmt($aud->obs ?? null) !!}</div>
                            </td>
                            <td class="px-3 py-2">
                                @php
                                    $acus = $aud->acusados ?? [];
                                    if (is_string($acus)) {
                                        $decoded = json_decode($acus, true);
                                        $acus = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
                                    }
                                    $acus = is_array($acus) ? $acus : [];
                                    $cntAcus = count($acus);
                                @endphp

                                @if ($cntAcus === 0)
                                    <span class="text-[10px] text-gray-400 italic">vacío</span>
                                @else
                                    <div x-data="{ open: false }" class="max-w-[300px]">
                                        <button type="button"
                                            class="inline-flex items-center gap-2 text-blue-700 hover:text-blue-900 border border-blue-200 hover:border-blue-300 rounded px-2 py-1 transition-colors"
                                            x-on:click="open = !open"
                                            :aria-expanded="open ? 'true' : 'false'"
                                            aria-controls="acusados-{{ $aud->id }}">
                                            <span class="font-medium">Acusados</span>
                                            <span class="text-xs bg-blue-50 border border-blue-200 rounded px-1">{{ $cntAcus }}</span>
                                            <svg x-show="!open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                            <svg x-show="open" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                                        </button>

                                        <div id="acusados-{{ $aud->id }}" class="mt-2" x-show="open" x-transition x-cloak>
                                            <ul class="list-disc pl-4 space-y-1">
                                                @foreach ($acus as $ac)
                                                    <li>
                                                        <span class="font-medium">{{ $ac['nombre_completo'] ?? '—' }}</span>
                                                        @if (!empty($ac['situacion'])) <span class="text-gray-500"> · {{ $ac['situacion'] }}</span> @endif
                                                        @if (!empty($ac['medida_cautelar'])) <span class="text-gray-500"> · {{ $ac['medida_cautelar'] }}</span> @endif
                                                        @if (!empty($ac['forma_notificacion'])) <span class="text-gray-500"> · {{ $ac['forma_notificacion'] }}</span> @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="px-3 py-2 text-right">
                                <button type="button"
                                    class="inline-flex items-center gap-1 text-red-600 transition-transform duration-150 ease-out hover:-translate-y-0.5 hover:shadow-sm active:translate-y-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/50 transform-gpu"
                                    x-on:click="
                                        if (confirm('¿Eliminar esta audiencia (RIT {{ $aud->rit }})?')) {
                                            $wire.eliminar({{ $aud->id }});
                                        }">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- ================= SLIDE-OVER DERECHO ================= --}}
    <div class="fixed inset-0 z-40" x-show="open" x-transition.opacity x-cloak aria-modal="true" role="dialog">
      <div class="absolute inset-0 bg-black/30" x-on:click="open = false"></div>

      <div class="absolute inset-y-0 right-0 w-full max-w-xl bg-white shadow-xl flex flex-col"
           x-show="open"
           x-transition:enter="transform transition ease-out duration-300"
           x-transition:enter-start="translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transform transition ease-in duration-200"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="translate-x-full">

        {{-- Header --}}
        <div class="px-4 py-3 border-b flex items-center justify-between">
          <h3 class="text-base font-semibold">Difusión por correo — {{ \Carbon\Carbon::parse($fecha)->format('d-m-Y') }}</h3>
          <button class="p-2 rounded hover:bg-gray-100" x-on:click="open = false" aria-label="Cerrar">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 011.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
          </button>
        </div>

        {{-- Body --}}
        <div class="p-4 space-y-6 flex-1 overflow-y-auto">

          {{-- Sección: Controles envío + botón administrar lista --}}
          <section class="space-y-3">
            <div class="flex items-center justify-between">
              <h4 class="font-medium">Destinatarios de este envío</h4>
              <button type="button"
                      class="text-sm px-3 py-1.5 rounded-md border border-gray-300 hover:bg-gray-50"
                      x-on:click="listModal = true; $wire.openListModal();">
                Administrar lista…
              </button>
            </div>

            {{-- chips resumidos (lo que se usará para ESTE envío) --}}
            <div class="flex flex-wrap gap-2">
              @forelse($recipients as $i => $rcpt)
                <span wire:key="chip-{{ $i }}-{{ md5($rcpt) }}"
                      class="inline-flex items-center gap-2 bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 rounded">
                  {{ $rcpt }}
                  <button type="button" class="text-blue-700 hover:text-blue-900" wire:click="removeRecipient({{ $i }})" aria-label="Quitar">
                    ✕
                  </button>
                </span>
              @empty
                <p class="text-xs text-gray-500">Sin destinatarios. Usa “Administrar lista…” para cargar los predeterminados o agregar.</p>
              @endforelse
            </div>

            {{-- agregar puntuales solo para este envío --}}
            <div class="flex gap-2">
              <input type="email"
                     wire:model.live="newEmail"
                     @keydown.enter.prevent="$wire.addRecipient()"
                     placeholder="Agregar sólo a este envío (correo@dominio.cl)"
                     class="flex-1 px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
              <button type="button" wire:click="addRecipient"
                      class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">Agregar</button>
            </div>
            @error('newEmail') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
          </section>

          <hr>

          {{-- Sección: Pie de firma --}}
          <section class="space-y-3">
            <h4 class="font-medium">Pie de firma</h4>

            <div class="grid md:grid-cols-2 gap-3">
              <div>
                <label class="block text-sm text-gray-700 mb-1">Seleccionar</label>
                <select wire:model.live="firmaId"
                        class="w-full px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
                  @foreach($signatures as $s)
                    <option value="{{ $s['id'] }}">{{ $s['nombre'] }}</option>
                  @endforeach
                </select>
              </div>
              <div class="md:text-right text-sm text-gray-500 self-end">Vista previa</div>
            </div>

            <div class="border rounded-md p-3 bg-gray-50">{!! $firmaPreview !!}</div>
          </section>

          <p class="text-xs text-gray-500">* Se adjuntará el Excel generado para la fecha seleccionada.</p>
        </div>

        {{-- Footer --}}
        <div class="px-4 py-3 border-t flex items-center justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded-md border hover:bg-gray-50" x-on:click="open = false">
            Cancelar
          </button>
          <button type="button"
                  wire:click="enviarCorreoDifusion"
                  class="px-4 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
            Enviar difusión
          </button>
        </div>
      </div>
    </div>

    {{-- ================= MODAL "ADMINISTRAR LISTA" (BD) ================= --}}
    <div class="fixed inset-0 z-[999] flex items-center justify-center" x-show="listModal" x-transition.opacity x-cloak>
      <div class="absolute inset-0 bg-black/40" x-on:click="listModal=false"></div>

      <div class="relative bg-white w-full max-w-2xl rounded-lg shadow-xl border">
        <div class="px-4 py-3 border-b flex items-center justify-between">
          <h3 class="text-base font-semibold">Lista de difusión (base de datos)</h3>
          <button class="p-2 rounded hover:bg-gray-100" x-on:click="listModal=false" aria-label="Cerrar">✕</button>
        </div>

        <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">
          <div class="flex gap-2">
            <input type="email"
                   wire:model.defer="newEmailDb"
                   @keydown.enter.prevent="$wire.addRecipientToDb()"
                   placeholder="correo@dominio.cl"
                   class="flex-1 px-3 py-2 border rounded-md border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button type="button" wire:click="addRecipientToDb"
                    class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700">
              Agregar a la lista
            </button>
          </div>
          @error('newEmailDb') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

          <div class="border rounded-md">
            <div class="px-3 py-2 bg-gray-50 border-b text-sm">
              Total: <strong>{{ count($recipientsDb) }}</strong>
            </div>
            <ul class="divide-y max-h-[48vh] overflow-y-auto">
              @forelse($recipientsDb as $row)
                <li class="flex items-center justify-between px-3 py-2 text-sm">
                  <span class="truncate">{{ $row['email'] }}</span>
                  <button type="button"
                          class="text-red-600 hover:text-red-800"
                          wire:click="removeRecipientFromDb({{ $row['id'] }})">
                    Eliminar
                  </button>
                </li>
              @empty
                <li class="px-3 py-2 text-sm text-gray-500">No hay correos en la lista.</li>
              @endforelse
            </ul>
          </div>

          <div class="text-xs text-gray-500">
            Nota: esta lista se guarda en la BD y se usa como predeterminada cuando abres “Enviar por correo”.
          </div>
        </div>

        <div class="px-4 py-3 border-t flex items-center justify-end gap-2">
          <button type="button" class="px-3 py-2 rounded-md border hover:bg-gray-50"
                  x-on:click="listModal=false">
            Cerrar
          </button>
          <button type="button" class="px-3 py-2 rounded-md border hover:bg-gray-50"
                  wire:click="refreshRecipientsFromDb">
            Refrescar
          </button>
          <button type="button" class="px-3 py-2 rounded-md bg-blue-600 text-white hover:bg-blue-700"
                  wire:click="loadDbIntoSendList">
            Usar esta lista en el envío
          </button>
        </div>
      </div>
    </div>

    @script
    <script>
        document.addEventListener('audiencia-eliminada', () => {
            Swal?.fire?.({ icon:'success', title:'Eliminada', timer:1200, showConfirmButton:false });
        });
        document.addEventListener('correo-enviado', () => {
            Swal?.fire?.({ icon:'success', title:'Correo enviado', timer:1500, showConfirmButton:false });
        });
    </script>
    @endscript
</div>
