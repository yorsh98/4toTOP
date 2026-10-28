<div class="fo-ausencias" x-data>
    @php
        $mes = \Carbon\Carbon::create($year, $month, 1);
        $dias = $gridDays;
        while (count($dias) < 42) {
            $dias[] = \Carbon\Carbon::parse(end($dias))->addDay()->toDateString();
        }
        $leyenda = [
            'licencia-medica' => 'Licencia médica',
            'permiso-347' => 'Permisos',
            'feriado-legal' => 'Feriado legal',
            'curso-academia' => 'Curso o comisión',
            'otro' => 'Otro',
        ];
    @endphp

    <div class="fo-listbar fo-cal-bar">
        <button type="button" class="fo-ttypp-button" wire:click="previousMonth" aria-label="Mes anterior">‹</button>
        <h2>{{ mb_convert_case($mes->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8') }}</h2>
        <button type="button" class="fo-ttypp-button" wire:click="nextMonth" aria-label="Mes siguiente">›</button>
        <div class="fo-cal-legend" role="group" aria-label="Tipos de ausencia">
            @foreach ($leyenda as $tipo => $label)
                <span class="fo-cal-legend-item" data-tipo="{{ $tipo }}"><span aria-hidden="true"></span>{{ $label }}</span>
            @endforeach
        </div>
    </div>

    <div class="fo-table-wrap fo-table-wrap--glass">
        <table class="fo-table fo-cal">
            <caption class="sr-only">Calendario de ausencias de {{ $mes->translatedFormat('F Y') }}</caption>
            <thead><tr>
                @foreach (['Lunes' => 'Lun', 'Martes' => 'Mar', 'Miércoles' => 'Mié', 'Jueves' => 'Jue', 'Viernes' => 'Vie', 'Sábado' => 'Sáb', 'Domingo' => 'Dom'] as $nombre => $abreviado)
                    <th scope="col" abbr="{{ $nombre }}">{{ $abreviado }}</th>
                @endforeach
            </tr></thead>
            <tbody>
                @foreach (array_chunk($dias, 7) as $semana)
                    <tr>
                        @foreach ($semana as $date)
                            @php
                                $dia = \Carbon\Carbon::parse($date);
                                $events = $eventsByDate[$date] ?? [];
                            @endphp
                            <td class="{{ $dia->month !== $month ? 'fo-cal-outside' : '' }} {{ $dia->isToday() ? 'fo-cal-today' : '' }}">
                                @if ($events)
                                    <button type="button" class="fo-cal-day" wire:click="openDay('{{ $date }}')" aria-label="Ver {{ count($events) }} {{ count($events) === 1 ? 'ausencia' : 'ausencias' }} del {{ $dia->translatedFormat('j \d\e F \d\e Y') }}">
                                @else
                                    <div class="fo-cal-day">
                                @endif
                                    <span class="fo-cal-day-head"><span class="fo-num fo-cal-number">{{ $dia->day }}</span>@if ($events)<span class="fo-badge fo-badge--num">{{ count($events) }}</span>@endif</span>
                                    <span class="fo-cal-events">
                                        @foreach (array_slice($events, 0, 3) as $ev)
                                            <span class="fo-cal-pill" data-tipo="{{ \Illuminate\Support\Str::slug(\Illuminate\Support\Str::ascii($ev['tipo'] ?? '')) }}">{{ $ev['nombre'] }}</span>
                                        @endforeach
                                        @if (count($events) > 3)<span class="fo-cal-more">+{{ count($events) - 3 }} más</span>@endif
                                    </span>
                                @if ($events)</button>@else</div>@endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($modalDate)
        @php
            $dia = \Carbon\Carbon::parse($modalDate);
            $events = $eventsByDate[$modalDate] ?? [];
        @endphp
        {{-- Fuera del contenido: la animación de entrada de la página rompería position: fixed --}}
        @teleport('body')
        <div class="fo-ausencias fo-cal-panel">
        <div class="fo-scrim" wire:click="closeModal" aria-hidden="true"></div>
        <section class="fo-drawer" role="dialog" aria-modal="true" aria-labelledby="ausencias-dia-titulo" x-on:keydown.escape.window="$wire.closeModal()">
            <div class="fo-drawer-head">
                <h2 class="fo-drawer-title" id="ausencias-dia-titulo">Ausencias del {{ $dia->translatedFormat('j \d\e F \d\e Y') }}</h2>
                <button type="button" class="fo-ttypp-button" wire:click="closeModal">Cerrar</button>
            </div>
            <div class="fo-drawer-body">
                <ul class="fo-cal-list">
                    @forelse ($events as $ev)
                        <li>
                            <strong>{{ $ev['nombre'] }}</strong>
                            @if ($ev['cargo'])<span>{{ $ev['cargo'] }}</span>@endif
                            @if (filled($ev['tipo'] ?? null))<span class="fo-cal-pill" data-tipo="{{ \Illuminate\Support\Str::slug(\Illuminate\Support\Str::ascii($ev['tipo'])) }}">{{ $ev['tipo'] }}</span>@endif
                            <span class="fo-num">{{ $ev['rango'] }}</span>
                            @if ($ev['obs'])<span>{{ $ev['obs'] }}</span>@endif
                        </li>
                    @empty
                        <li>Nadie ausente este día.</li>
                    @endforelse
                </ul>
            </div>
        </section>
        </div>
        @endteleport
    @endif
</div>
