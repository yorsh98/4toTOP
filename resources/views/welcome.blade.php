@php
    $catalog = app(\App\Services\FrontOfficeCatalog::class);
    $sections = $catalog->sections();
    $groups = $catalog->grouped();

    $estados = [
        'EN_CURSO' => 'en curso',
        'RECESO' => 'en receso',
        'POR_REALIZARSE' => 'por realizarse',
        'FINALIZADA' => 'finalizadas',
    ];
    $audienciasHoy = collect($audienciasHoy ?? []);
    $totalHoy = $audienciasHoy->sum();
    $enCurso = (int) $audienciasHoy->get('EN_CURSO', 0);

    $nombres = fn ($turno, array $campos) => collect($campos)
        ->map(fn ($campo) => trim((string) ($turno->{$campo} ?? '')))
        ->filter()
        ->values();

    $turnos = [
        [
            'id' => 'semanal',
            'title' => 'Turno semanal',
            'meta' => null,
            'grupos' => [
                'Magistrados' => $nombres($turno1, ['TM1', 'TM2', 'TM3']),
                'Funcionarios' => $nombres($turno1, ['TF1', 'TF2', 'TF3']),
                'Jefatura' => $nombres($turno1, ['TJ1', 'TJ2', 'TJ3']),
            ],
        ],
        [
            'id' => 'acd',
            'title' => 'Turno ACD',
            'meta' => filled($turno2->FECHA ?? null) ? "Próximo turno: {$turno2->FECHA}" : null,
            'grupos' => [
                'Magistrados' => $nombres($turno2, ['ACDM1', 'ACDM2', 'ACDM3']),
                'Funcionarios' => $nombres($turno2, ['ACDF1', 'ACDF2', 'ACDF3']),
                'Jefatura' => $nombres($turno2, ['ACDJ1', 'ACDJ2', 'ACDJ3']),
            ],
        ],
    ];
    $encargados = collect($encargadosAudiencia ?? []);
    $ahora = now('America/Santiago');
    $clima = $clima ?? null;
@endphp

<x-public.layout :home="true">
    {{-- ===== Portada ===== --}}
    <section class="fo-hero" aria-labelledby="fo-hero-title">
        <div class="fo-hero-inner">
            <p class="fo-eyebrow fo-hero-in" style="--d: 0">4º Tribunal de Juicio Oral en lo Penal de Santiago</p>

            <h1 id="fo-hero-title" class="fo-title" data-fo-chrome aria-label="4toTOP">
                @foreach (mb_str_split('4TO TOP') as $i => $char)
                    @if ($char === ' ')
                        <span class="fo-title-gap" aria-hidden="true"></span>
                    @else
                        <span class="fo-ch" style="--i: {{ $i }}" aria-hidden="true">{{ $char }}</span>
                    @endif
                @endforeach
            </h1>

            <p class="fo-hero-lead fo-hero-in" style="--d: 3">Turnos, audiencias y herramientas del tribunal en un solo lugar.</p>

            <button type="button" class="fo-hero-search cm-press fo-hero-in" style="--d: 4" data-fo-palette>
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <span>Buscar un acceso…</span>
                <kbd class="fo-kbd">Ctrl K</kbd>
            </button>
        </div>

        {{-- Tablero del día --}}
        <div class="fo-board">
            <span class="fo-sweep" aria-hidden="true"></span>
            <div class="fo-board-grid cm-welcome">
                <div class="fo-cell fo-cell--clock" style="--cm-i: 0">
                    <span class="fo-cell-label">{{ $clima ? 'Hora y clima en Santiago' : 'Hora en Santiago' }}</span>
                    <div class="fo-now">
                        <div class="fo-now-time">
                            <time class="fo-clock" data-fo-clock datetime="{{ $ahora->toIso8601String() }}">{{ $ahora->format('H:i:s') }}</time>
                            <span class="fo-cell-meta" data-fo-date>{{ ucfirst($ahora->locale('es')->isoFormat('dddd D [de] MMMM')) }}</span>
                        </div>
                        @if ($clima)
                            <div class="fo-wx">
                                <x-public.weather-icon :kind="$clima['kind']" :day="$clima['is_day']" />
                                <div class="fo-wx-text">
                                    <span class="fo-wx-temp">{{ $clima['temperature'] }}°</span>
                                    <span class="fo-cell-meta">{{ $clima['label'] }}</span>
                                    <span class="fo-cell-meta">Máx {{ $clima['max'] }}° · Mín {{ $clima['min'] }}°</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <button type="button" class="fo-cell fo-cell--action" style="--cm-i: 1" data-fo-open="estado" aria-haspopup="dialog">
                    <span class="fo-cell-label">
                        @if ($enCurso > 0)
                            <span class="cm-online fo-live" aria-hidden="true"></span>
                        @endif
                        Audiencias de hoy
                    </span>
                    <span class="fo-cell-value"><b class="fo-num">{{ $totalHoy }}</b> {{ $totalHoy === 1 ? 'programada' : 'programadas' }}</span>
                    <span class="fo-cell-meta">
                        @if ($totalHoy === 0)
                            Sin audiencias cargadas para hoy.
                        @else
                            {{ collect($estados)->filter(fn ($label, $key) => $audienciasHoy->get($key))->map(fn ($label, $key) => $audienciasHoy->get($key).' '.$label)->implode(' · ') }}
                        @endif
                    </span>
                    <span class="fo-cell-cue">Ver estado <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                </button>

                <button type="button" class="fo-cell fo-cell--action" style="--cm-i: 2" data-fo-open="turnos" aria-haspopup="dialog">
                    <span class="fo-cell-label">Turno semanal</span>
                    <span class="fo-cell-value">{{ $turnos[0]['grupos']['Magistrados']->first() ?? 'Sin asignar' }}</span>
                    <span class="fo-cell-meta">Magistrados, funcionarios y jefatura</span>
                    <span class="fo-cell-cue">Ver turno <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                </button>

                <button type="button" class="fo-cell fo-cell--action" style="--cm-i: 3" data-fo-open="turnos" data-fo-target="fo-turno-acd" aria-haspopup="dialog">
                    <span class="fo-cell-label">Turno ACD</span>
                    <span @class(['fo-cell-value', 'fo-num' => filled($turno2->FECHA ?? null)])>{{ filled($turno2->FECHA ?? null) ? $turno2->FECHA : 'Sin fecha' }}</span>
                    <span class="fo-cell-meta">Próximo turno</span>
                    <span class="fo-cell-cue">Ver turno <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                </button>

                <button type="button" class="fo-cell fo-cell--action" style="--cm-i: 4" data-fo-open="encargados" aria-haspopup="dialog">
                    <span class="fo-cell-label">Nº encargado de audiencia</span>
                    <span class="fo-cell-value"><b class="fo-num">{{ $encargados->count() }}</b> {{ $encargados->count() === 1 ? 'encargado' : 'encargados' }}</span>
                    <span class="fo-cell-meta">Números asignados a cada uno</span>
                    <span class="fo-cell-cue">Ver números <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></span>
                </button>
            </div>
        </div>
    </section>

    {{-- ===== Secciones ===== --}}
    @foreach ($groups as $id => $items)
        <section id="{{ $id }}" class="fo-section fo-section--{{ $id }}" data-fo-section aria-labelledby="fo-{{ $id }}-title">
            <header class="fo-section-head" data-reveal>
                <h2 id="fo-{{ $id }}-title" class="fo-section-title">{{ $sections[$id]['title'] }}</h2>
                <p class="fo-section-lead">{{ $sections[$id]['lead'] }}</p>
            </header>

            @switch($id)
                @case('sistemas')
                    <div class="fo-grid fo-grid--shots">
                        @foreach ($items as $item)
                            <x-public.access :item="$item" variant="shot" />
                        @endforeach
                    </div>
                    @break

                @case('audiencias')
                    <div class="fo-grid fo-grid--split">
                        <x-public.access :item="$items[0]" variant="feature" />
                        <div class="fo-stack">
                            @foreach (array_slice($items, 1) as $item)
                                <x-public.access :item="$item" variant="row" />
                            @endforeach
                        </div>
                    </div>
                    @break

                @case('herramientas')
                    <div class="fo-grid fo-grid--tiles">
                        @foreach ($items as $item)
                            <x-public.access :item="$item" variant="tile" />
                        @endforeach
                    </div>
                    @break

                @default
                    <div class="fo-grid fo-grid--rows">
                        @foreach ($items as $item)
                            <x-public.access :item="$item" variant="row" />
                        @endforeach
                    </div>
            @endswitch
        </section>
    @endforeach

    <x-slot:drawers>
        <x-public.drawer id="turnos" title="Turnos" lead="Quién está de turno esta semana y en el próximo turno ACD.">
            @foreach ($turnos as $turno)
                <section class="fo-turno" id="fo-turno-{{ $turno['id'] }}">
                    <h3 class="fo-turno-title">{{ $turno['title'] }}</h3>
                    @if ($turno['meta'])
                        <p class="fo-turno-meta fo-num">{{ $turno['meta'] }}</p>
                    @endif
                    <dl class="fo-turno-groups">
                        @foreach ($turno['grupos'] as $grupo => $personas)
                            <div>
                                <dt>{{ $grupo }}</dt>
                                @forelse ($personas as $persona)
                                    <dd>{{ $persona }}</dd>
                                @empty
                                    <dd class="fo-muted">Sin asignar</dd>
                                @endforelse
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach
        </x-public.drawer>

        <x-public.drawer id="encargados" title="Nº encargado de audiencia" lead="Números que tiene asignados cada encargado.">
            <ul class="fo-list">
                @forelse ($encargados as $encargado)
                    <li style="--cm-i: {{ $loop->index }}">
                        <span>{{ $encargado['nombre'] }}</span>
                        <span class="fo-num fo-list-value">{{ $encargado['numeros'] }}</span>
                    </li>
                @empty
                    <li class="fo-muted">Aún no hay encargados configurados. Se agregan desde el backoffice, en Documentos.</li>
                @endforelse
            </ul>
        </x-public.drawer>

        <x-public.drawer id="estado" title="Estado de audiencias" lead="Elige un tipo de audiencia para ver las de hoy.">
            <livewire:cambiar-estado-audiencia />
        </x-public.drawer>
    </x-slot:drawers>
</x-public.layout>
