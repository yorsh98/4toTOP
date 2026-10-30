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
        @php
            $anfitriones = collect($cortas)->pluck('anfitrion')->filter(fn ($nombre) => filled($nombre))->unique()->values();
        @endphp
        @foreach ($grupos as $id => [$titulo, $audiencias, $vacio])
            <section id="{{ $id }}" class="fo-progra-section fo-progra-{{ $id }}" aria-labelledby="titulo-{{ $id }}">
                <h2 id="titulo-{{ $id }}" class="fo-progra-title">{{ $titulo }}
                    @if ($id === 'cortas' && $anfitriones->isNotEmpty())
                        <span class="fo-progra-host">Anfitrión/a{{ $anfitriones->count() > 1 ? 'es' : '' }}: {{ $anfitriones->take(3)->join(', ') }}@if ($anfitriones->count() > 3) +{{ $anfitriones->count() - 3 }}@endif</span>
                    @endif
                </h2>
                @forelse ($audiencias as $audiencia)
                    <article class="fo-table-wrap--glass fo-progra-card">
                        <header class="fo-progra-card-head">{{ $audiencia['encabezado'] }}</header>
                        <table class="fo-progra-fields">
                            <caption class="sr-only">Datos de {{ $audiencia['encabezado'] }}</caption>
                            <tbody>
                                @if ($id === 'juicios')
                                    <tr><th scope="row">Delito</th><td colspan="3">{{ $audiencia['delito'] ?? '—' }}</td></tr>
                                    @php $filas = [['Testigos', 'num_testigos', 'Peritos', 'num_peritos'], ['Jueces Inhabilitados', 'inhabil', 'Encargado Causa', 'encargado'], ['Juez Presidente', 'juez_p', 'Acta de sala', 'acta'], ['Juez Redactor', 'juez_r', 'Encargados de TTyPP', 'ttpp'], ['Juez Integrante', 'juez_i', 'Encargado TTyPP (Zoom)', 'ttpp_zoom']]; @endphp
                                @elseif ($id === 'lecturas')
                                    @php $filas = [['Acta de Audiencia', 'acta', 'Encargado Causa', 'encargado'], ['Juez Redactor', 'juez_r', null, null]]; @endphp
                                @else
                                    @php $filas = [['Juez Presidente', 'juez_p', 'Acta de Audiencia', 'acta'], ['Juez Redactor', 'juez_r', 'Encargado Causa', 'encargado'], ['Juez Integrante', 'juez_i', null, null]]; @endphp
                                @endif
                                @foreach ($filas as [$etiqueta, $campo, $segundaEtiqueta, $segundoCampo])
                                    <tr>
                                        <th scope="row">{{ $etiqueta }}</th><td>{{ $audiencia[$campo] ?? '—' }}</td>
                                        @if ($segundaEtiqueta)
                                            <th scope="row">{{ $segundaEtiqueta }}</th><td>{{ $audiencia[$segundoCampo] ?? '—' }}</td>
                                        @else
                                            <td colspan="2"><a class="fo-ttypp-button" href="{{ $id === 'lecturas' ? 'https://zoom.us/j/96002644455?pwd=TVpxNG5td010ekRwV0RzZzdiRXVSQT09' : 'https://zoom.us/j/94505473191?pwd=ZWJYa0ZWSG1naXlNK3V3T2pZNjZVdz09' }}" target="_blank" rel="noopener noreferrer">Unirse a Zoom</a></td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        @if (!empty($audiencia['acusados']))
                            @php $acusados = array_values($audiencia['acusados']); $compacto = count($acusados) > 4; @endphp
                            <div class="fo-progra-people">
                                <h3>Acusados</h3>
                                <table class="fo-progra-people-table">
                                    <caption class="sr-only">Acusados de {{ $audiencia['encabezado'] }}</caption>
                                    <thead><tr>
                                        <th scope="col">{{ $id === 'juicios' && ! $compacto ? 'Acusado' : 'Acusados' }}</th><th scope="col">{{ $id === 'cortas' && ! $compacto ? 'Situación de Libertad' : 'Situación' }}</th>
                                        @if ($compacto)<th scope="col">Acusados</th><th scope="col">Situación</th>@elseif ($id === 'juicios')<th scope="col">Medidas Cautelares</th><th scope="col">Forma de Notificación</th>@endif
                                    </tr></thead>
                                    <tbody>
                                        @foreach (array_chunk($acusados, $compacto ? 2 : 1) as $par)
                                            <tr>
                                                @foreach ($par as $acusado)
                                                    <td data-label="Acusados"><strong>{{ $acusado['nombre'] ?? '—' }}</strong></td>
                                                    <td data-label="Situación">@if (!empty($acusado['situacion']))<span class="fo-progra-tag">{{ $acusado['situacion'] }}</span>@else — @endif</td>
                                                    @if (! $compacto && $id === 'juicios')
                                                        <td data-label="Medidas Cautelares">@if (!empty($acusado['medidas']))<span class="fo-progra-tag">{{ $acusado['medidas'] }}</span>@else — @endif</td>
                                                        <td data-label="Forma de Notificación">@if (!empty($acusado['notificacion']))<span class="fo-progra-tag">{{ $acusado['notificacion'] }}</span>@else — @endif</td>
                                                    @endif
                                                @endforeach
                                                @if ($compacto && count($par) === 1)<td data-label="Acusados">—</td><td data-label="Situación">—</td>@endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="fo-progra-empty">{{ $vacio }}</p>
                @endforelse
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
