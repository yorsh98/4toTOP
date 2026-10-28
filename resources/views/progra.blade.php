<x-in-layout title="Programación diaria">
    @php
        $hoyStr = \Carbon\Carbon::now('America/Santiago')->toDateString();
        $prevStr = \Carbon\Carbon::parse($fecha, 'America/Santiago')->subDay()->toDateString();
        $nextStr = \Carbon\Carbon::parse($fecha, 'America/Santiago')->addDay()->toDateString();
        $grupos = [
            'juicios' => ['Juicios orales', $juicios, 'Sin juicios orales programados para este día.', ['delito' => 'Delito', 'num_testigos' => 'Testigos', 'num_peritos' => 'Peritos', 'inhabil' => 'Jueces inhabilitados', 'encargado' => 'Encargado causa', 'juez_p' => 'Juez presidente', 'acta' => 'Acta de sala', 'juez_r' => 'Juez redactor', 'ttpp' => 'Encargados de TTyPP', 'juez_i' => 'Juez integrante', 'ttpp_zoom' => 'Encargado TTyPP (Zoom)']],
            'lecturas' => ['Lecturas de sentencia', $lecturas, 'Sin lecturas de sentencia programadas para este día.', ['acta' => 'Acta de audiencia', 'encargado' => 'Encargado causa', 'juez_r' => 'Juez redactor']],
            'cortas' => ['Audiencias cortas', $cortas, 'Sin audiencias cortas programadas para este día.', ['juez_p' => 'Juez presidente', 'acta' => 'Acta de audiencia', 'juez_r' => 'Juez redactor', 'encargado' => 'Encargado causa', 'juez_i' => 'Juez integrante']],
        ];
    @endphp

    <x-public.page-head title="Programación diaria" :meta="ucfirst($fechaHuman)" />

    <div class="fo-progra-controls">
        <div class="fo-listbar fo-ttypp-bar fo-progra-bar">
            <a class="fo-ttypp-button" href="{{ url()->current() }}?fecha={{ $prevStr }}">‹ Anterior</a>
            <form method="GET" action="{{ url()->current() }}" class="fo-ttypp-date">
                <label class="sr-only" for="progra-fecha">Fecha</label>
                <input class="fo-num" id="progra-fecha" type="date" name="fecha" value="{{ $fecha }}">
                <button class="fo-ttypp-button" type="submit">Ver</button>
            </form>
            <a class="fo-ttypp-button" href="{{ url()->current() }}?fecha={{ $hoyStr }}">Hoy</a>
            <a class="fo-ttypp-button" href="{{ url()->current() }}?fecha={{ $nextStr }}">Siguiente ›</a>
            <form method="GET" action="{{ route('audiencias.export.diaria') }}" onsubmit="this.fecha.value = document.querySelector('#progra-fecha').value;">
                <input type="hidden" name="fecha" value="{{ $fecha }}">
                <button type="submit" class="fo-ttypp-button" title="Exportar a Excel"><i class="fa-solid fa-file-excel" aria-hidden="true"></i> Exportar Excel</button>
            </form>
        </div>
        <nav class="fo-progra-summary" aria-label="Resumen del día">
            @foreach ($grupos as $id => [$titulo, $audiencias])
                <a href="#{{ $id }}">{{ $id === 'lecturas' ? 'Lecturas' : $titulo }} <strong class="fo-num">{{ $audiencias->count() }}</strong></a>
            @endforeach
        </nav>
        <section aria-labelledby="progra-turnos">
            <h2 id="progra-turnos" class="fo-progra-title">Turnos del día</h2>
            <div class="fo-progra-turnos">
                @foreach ([$turno1, $turno2, $turno3] as $turno)
                    <div><strong>Turno {{ $loop->iteration }}</strong><p @class(['fo-muted' => ! $turno])>{{ $turno ?: 'Sin turno asignado' }}</p></div>
                @endforeach
            </div>
        </section>
    </div>

    <div class="fo-progra-groups">
        @foreach ($grupos as $id => [$titulo, $audiencias, $vacio, $detalles])
            <section id="{{ $id }}" class="fo-progra-section fo-progra-{{ $id }}" aria-labelledby="titulo-{{ $id }}">
                <h2 id="titulo-{{ $id }}" class="fo-progra-title">{{ $titulo }}</h2>
                <div class="fo-table-wrap fo-table-wrap--glass fo-progra-table">
                    <table class="fo-table">
                        <caption class="sr-only">{{ $titulo }} para {{ $fechaHuman }}</caption>
                        <thead><tr><th scope="col">Hora</th><th scope="col">Sala</th><th scope="col">RIT</th><th scope="col">RUC</th>@if ($id === 'juicios')<th scope="col">Tipo</th>@endif @if ($id === 'cortas')<th scope="col">Anfitrión/a</th>@endif<th scope="col"><span class="sr-only">Detalle</span></th></tr></thead>
                        @forelse ($audiencias as $audiencia)
                            <tbody class="fo-table-group" x-data="{ open: false }">
                                <tr>
                                    <td data-label="Hora" class="fo-num">{{ $audiencia['hora'] ?: '—' }}</td>
                                    <td data-label="Sala">{{ $audiencia['sala'] ? 'Sala '.$audiencia['sala'] : '—' }}@if ($audiencia['zoom'] ?? null) · {{ $audiencia['zoom'] }}@endif</td>
                                    <td data-label="RIT" class="fo-num">{{ $audiencia['rit'] ?: '—' }}</td>
                                    <td data-label="RUC" class="fo-num">{{ $audiencia['ruc'] ?: '—' }}</td>
                                    @if ($id === 'juicios')<td data-label="Tipo">{{ $audiencia['tipo'] ?: '—' }}</td>@endif
                                    @if ($id === 'cortas')<td data-label="Anfitrión/a">{{ $audiencia['anfitrion'] ?: '—' }}</td>@endif
                                    <td class="fo-table-toggle"><button type="button" class="fo-icon-btn cm-press" x-on:click="open = ! open" x-bind:aria-expanded="open" aria-expanded="false" aria-controls="progra-{{ $id }}-{{ $loop->index }}" aria-label="Ver detalle de {{ $audiencia['rit'] ?: $titulo }}"><i class="fa-solid fa-chevron-down" x-bind:class="open && 'fa-rotate-180'" aria-hidden="true"></i></button></td>
                                </tr>
                                <tr class="fo-table-detail" id="progra-{{ $id }}-{{ $loop->index }}" x-show="open" x-cloak><td colspan="{{ in_array($id, ['juicios', 'cortas']) ? 6 : 5 }}">
                                    <dl class="fo-detail">
                                        <div><dt>Observaciones</dt><dd>{{ $audiencia['obs'] ?: '—' }}</dd></div>
                                        @if ($id === 'juicios')<div><dt>Duración</dt><dd>{{ $audiencia['duracion'] ?: '—' }}</dd></div>@endif
                                        @foreach ($detalles as $campo => $etiqueta)
                                            <div><dt>{{ $etiqueta }}</dt><dd>{{ $audiencia[$campo] ?? '—' }}</dd></div>
                                        @endforeach
                                        @if (!empty($audiencia['acusados']))
                                            <div><dt>Acusados</dt><dd><ul class="fo-progra-acusados">
                                                @foreach ($audiencia['acusados'] as $acusado)
                                                    <li><strong>{{ $acusado['nombre'] ?? '—' }}</strong> · Situación: {{ $acusado['situacion'] ?? '—' }}@if ($id === 'juicios' && count($audiencia['acusados']) <= 4) · Medidas cautelares: {{ $acusado['medidas'] ?? '—' }} · Forma de notificación: {{ $acusado['notificacion'] ?? '—' }}@endif</li>
                                                @endforeach
                                            </ul></dd></div>
                                        @endif
                                        @if ($id === 'lecturas' || $id === 'cortas')
                                            <div><dt>Zoom</dt><dd><a class="fo-link" href="{{ $id === 'lecturas' ? 'https://zoom.us/j/96002644455?pwd=TVpxNG5td010ekRwV0RzZzdiRXVSQT09' : 'https://zoom.us/j/94505473191?pwd=ZWJYa0ZWSG1naXlNK3V3T2pZNjZVdz09' }}" target="_blank" rel="noopener noreferrer">Unirse a Zoom</a></dd></div>
                                        @endif
                                    </dl>
                                </td></tr>
                            </tbody>
                        @empty
                            <tbody><tr><td colspan="{{ in_array($id, ['juicios', 'cortas']) ? 6 : 5 }}" class="fo-table-empty">{{ $vacio }}</td></tr></tbody>
                        @endforelse
                    </table>
                </div>
            </section>
        @endforeach
    </div>

    <section class="fo-progra-absences" aria-labelledby="progra-ausentes">
        <h2 id="progra-ausentes" class="fo-progra-title">Jueces ausentes</h2>
        @forelse ($juecesAusentes as $juez)
            <div><strong>{{ $juez['nombre'] }}</strong><span>{{ $juez['funcion'] }}</span></div>
        @empty
            <p>No hay jueces ausentes registrados.</p>
        @endforelse
    </section>
</x-in-layout>
