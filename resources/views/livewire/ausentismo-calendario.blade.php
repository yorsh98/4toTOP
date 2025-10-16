<div class="w-full" x-data>
    @php
        $yy = isset($year) ? $year : now()->year;
        $mm = isset($month) ? $month : now()->month;
    @endphp

    {{-- Header con navegación --}}
    <div class="flex items-center justify-between mb-4">
        <button wire:click="previousMonth" class="px-3 py-2 rounded-lg border hover:bg-gray-50">← Mes anterior</button>
        <div class="text-xl font-semibold">
            {{ \Carbon\Carbon::create($yy, $mm, 1)->translatedFormat('F Y') }}
        </div>
        <button wire:click="nextMonth" class="px-3 py-2 rounded-lg border hover:bg-gray-50">Mes siguiente →</button>
    </div>

    {{-- (Opcional) Leyenda de colores --}}
    @php
    $legend = [
        'Licencia Medica'            => 'bg-yellow-300 text-yellow-900',
        'Permiso 347'                => 'bg-red-500 text-white',
        'Permiso sin goce de sueldo' => 'bg-red-500 text-white',
        'Feriado Legal'              => 'bg-green-300 text-green-900',
        'Curso Academia'             => 'bg-blue-400 text-white',
        'Comision de servicio'       => 'bg-blue-400 text-white',
        'Otro'                       => 'bg-gray-300 text-gray-900',
    ];
    @endphp

    <div class="flex flex-wrap items-center gap-2 mb-3">
      @foreach($legend as $label => $cls)
        <span class="inline-flex items-center gap-2 text-xs px-2 py-1 rounded {{ $cls }}">
          <i class="w-2 h-2 rounded-full bg-black/20 inline-block"></i>{{ $label }}
        </span>
      @endforeach
    </div>

    {{-- Nombres de días --}}
    <div class="grid grid-cols-7 text-center text-sm font-medium text-gray-600">
        @foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $dayName)
            <div class="py-2">{{ $dayName }}</div>
        @endforeach
    </div>

    {{-- Grilla 7x6 --}}
    <div class="grid grid-cols-7 gap-2">
        @php
            $firstOfMonth = \Carbon\Carbon::create($yy, $mm, 1);
        @endphp

        @foreach ($gridDays as $date)
            @php
                $c = \Carbon\Carbon::parse($date);
                $isCurrentMonth = $c->month === $mm;
                $isToday = $c->isToday();
                $events = $eventsByDate[$date] ?? [];
                $extraCount = max(count($events) - 3, 0);
            @endphp

            <div class="min-h-28 p-2 rounded-xl border
                        {{ $isCurrentMonth ? 'bg-white' : 'bg-gray-50 border-gray-200' }}
                        {{ $isToday ? 'ring-2 ring-blue-500' : '' }}">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-sm {{ $isCurrentMonth ? 'text-gray-800' : 'text-gray-400' }}">
                        {{ $c->day }}
                    </span>
                    @if($events)
                        <button wire:click="openDay('{{ $date }}')" class="text-xs underline">ver</button>
                    @endif
                </div>

                {{-- Pills con hasta 3 ausencias --}}
                <div class="space-y-1">
                    @foreach (array_slice($events, 0, 3) as $ev)
                        <div
                          class="text-xs px-2 py-1 rounded-lg truncate {{ $ev['color'] ?? 'bg-gray-200 text-gray-800' }}"
                          title="{{ $ev['tipo'] ?? 'Tipo' }}"
                        >
                            <span class="font-semibold">{{ $ev['nombre'] ?? '—' }}</span>
                            @if(!empty($ev['cargo']))
                              <span class="text-gray-700/80">({{ $ev['cargo'] }})</span>
                            @endif
                        </div>
                    @endforeach

                    @if($extraCount > 0)
                        <div class="text-xs px-2 py-1 rounded-lg bg-gray-200 text-gray-700">
                            +{{ $extraCount }} más
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Modal simple para el día seleccionado --}}
    @if($modalDate)
        @php
            $c = \Carbon\Carbon::parse($modalDate);
            $events = $eventsByDate[$modalDate] ?? [];
        @endphp
        <div class="fixed inset-0 bg-black/30 flex items-center justify-center z-50">
            <div class="bg-white w-full max-w-lg rounded-2xl shadow-xl">
                <div class="p-4 border-b flex items-center justify-between">
                    <h3 class="text-lg font-semibold">Ausencias — {{ $c->translatedFormat('l d \\d\\e F, Y') }}</h3>
                    <button wire:click="closeModal" class="px-2 py-1 rounded border hover:bg-gray-50">Cerrar</button>
                </div>
                <div class="p-4 space-y-3 max-h-[60vh] overflow-auto">
                    @forelse($events as $ev)
                        <div class="p-3 rounded-xl border">
                            <div class="font-semibold">
                                {{ $ev['nombre'] ?? '—' }}
                                @if(!empty($ev['cargo']))
                                  <span class="text-gray-600">({{ $ev['cargo'] }})</span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-700">Rango: {{ $ev['rango'] ?? '—' }}</div>
                            @if(!empty($ev['tipo']))
                                <div class="text-xs mt-1">
                                  <span class="px-2 py-0.5 rounded {{ $ev['color'] ?? 'bg-gray-200 text-gray-800' }}">{{ $ev['tipo'] }}</span>
                                </div>
                            @endif
                            @if(!empty($ev['obs']))
                                <div class="text-sm text-gray-700 mt-1">Obs: {{ $ev['obs'] }}</div>
                            @endif
                        </div>
                    @empty
                        <div class="text-sm text-gray-600">Sin ausencias.</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif
</div>
