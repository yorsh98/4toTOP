@php
    // Anfitriones (badge en Audiencias Cortas)
    $anfitrionesCortas = collect($cortas ?? [])
        ->pluck('anfitrion')
        ->filter(fn($v) => filled($v))
        ->unique()
        ->values();
@endphp

<x-in-layout>
@push('styles')
  {{-- Font Awesome 6 (íconos) --}}
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>
@endpush

<style>
/* ========= ESTILOS PROGRA ========= */
.progra{
  --juicio-oral:#4285f4; --audiencia-corta:#34a853; --lectura:#9c27b0;
  --turno-1:#4285f4; --turno-2:#ea4335; --turno-3:#fbbc05;
  --fondo-tabla:#f8f9fa; --borde:#e0e0e0; --texto:#202124; --sec:#5f6368;
  color:var(--texto);
}
.progra *{ box-sizing:border-box }
.progra .container{ max-width:100%; width:100%; margin:0 auto }

/* Header */
.progra .header{
  background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);
  padding:20px;margin-bottom:22px;position:relative;overflow:hidden;
}
.progra .header::before{
  content:'';position:absolute;inset:0 0 auto 0;height:4px;
  background:linear-gradient(90deg,var(--juicio-oral),var(--audiencia-corta),var(--lectura));
}
.progra .header h1{
  font-weight:700;font-size:32px;margin:8px 0 6px;display:flex;gap:12px;align-items:center;justify-content:center;
}
.progra .header h2{
  color:var(--sec);font-weight:500;font-size:18px;display:flex;gap:8px;align-items:center;justify-content:center;
}

/* Toolbar de fecha */
.progra .fecha-toolbar{
  display:flex;gap:10px;align-items:center;justify-content:center;margin-top:14px;flex-wrap:wrap;
}
.progra .fecha-toolbar .btn{
  display:inline-flex;align-items:center;gap:6px;background:#fff;border:1px solid #e5e7eb;border-radius:8px;
  padding:6px 10px;font-weight:600;text-decoration:none;color:#111827;box-shadow:0 1px 2px rgba(0,0,0,.04);transition:all .15s;
}
.progra .fecha-toolbar .btn:hover{ border-color:#cbd5e1; transform:translateY(-1px) }
.progra .fecha-toolbar input[type="date"]{
  border:1px solid #e5e7eb;border-radius:8px;padding:6px 10px;font-weight:600;
}

/* Turnos */
.progra .turnos-container{
  background:#fff;border-radius:12px;padding:20px;box-shadow:0 4px 12px rgba(0,0,0,.08);margin-bottom:30px;
}
.progra .turnos-title{
  font-weight:700;text-align:center;margin-bottom:20px;font-size:20px;display:flex;gap:10px;align-items:center;justify-content:center;
}
.progra .turnos-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:20px}
.progra .turno-card{
  background:var(--fondo-tabla);border-radius:10px;padding:20px;border-left:4px solid;
  transition:.2s;position:relative;box-shadow:0 0 0 rgba(0,0,0,0);
}
.progra .turno-card:hover{ transform:translateX(5px); background:#e8f0fe; box-shadow:0 6px 16px rgba(66,133,244,.12) }
.progra .turno-1{border-left-color:var(--turno-1)}
.progra .turno-2{border-left-color:var(--turno-2)}
.progra .turno-3{border-left-color:var(--turno-3)}
.progra .etiqueta-turno{display:inline-flex;align-items:center;padding:6px 16px;border-radius:20px;font-weight:700;color:#fff;gap:8px}
.progra .turno-1 .etiqueta-turno{background:var(--turno-1)}
.progra .turno-2 .etiqueta-turno{background:var(--turno-2)}
.progra .turno-3 .etiqueta-turno{background:var(--turno-3)}
.progra .turno-content{font-size:17px;font-weight:600;padding-left:10px;padding-top:10px}
.progra .sin-turno{color:#95a5a6;font-style:italic}

/* Secciones */
.progra .audiencias-container{display:block;margin-bottom:40px}
.progra .seccion-audiencias{
  background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,.08);width:100%;margin-bottom:30px;
}
.progra .seccion-titulo{
  font-size:20px;font-weight:700;color:#fff;padding:18px 20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;
}
.progra .titulo-juicio{background:var(--juicio-oral)}
.progra .titulo-lectura{background:var(--lectura)}
.progra .titulo-audiencia{background:var(--audiencia-corta)}
.progra .meta-seccion{
  font-weight:500;font-size:.9em;opacity:.9;margin-left:.6rem;padding:2px 8px;border-radius:999px;background:rgba(255,255,255,.18);color:#fff;white-space:nowrap;
}

/* Tarjeta */
.progra .audiencia-card{background:#fff;border-radius:10px;overflow:hidden;border:1px solid var(--borde);margin:20px 0}
.progra .card-header{padding:10px 14px;color:#fff;font-weight:600}
.progra .header-juicio{background:var(--juicio-oral)}
.progra .header-lectura{background:var(--lectura)}
.progra .header-audiencia{background:var(--audiencia-corta)}
.progra .card-body{padding:0}

/* Tablas */
.progra .tabla-juicio{width:100%;border-collapse:collapse;font-size:0.92em}
.progra .tabla-juicio th,.progra .tabla-juicio td{
  border:1px solid #ddd;padding:6px 8px;text-align:left;vertical-align:top;line-height:1.25;
}
.progra .tabla-juicio th{background-color:#f2f2f2;font-weight:600;width:25%}
.progra .texto-centrado{text-align:center}

/* Acusados */
.progra .acusados-section{margin:15px 20px 20px;border-top:1px dashed #eee;padding-top:12px}
.progra .acusados-title{font-weight:700;color:#2c3e50;margin:0 0 10px}
.progra .acusados-section .tabla-juicio{font-size:0.85em}
.progra .acusados-section .tabla-juicio th,.progra .acusados-section .tabla-juicio td{padding:5px 7px}
.progra .tag{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.75em;font-weight:700}
.progra .tag.situacion{background:#e6f4ea;color:#137333}
.progra .tag.medidas{background:#fce8e6;color:#a50e0e}
.progra .tag.notificacion{background:#e8f0fe;color:#174ea6}

/* Botón Zoom */
.progra .btn-zoom{
  display:inline-block;background:#2d8cff;color:#fff;padding:6px 10px;border-radius:6px;text-decoration:none;font-weight:600;transition:background .2s;
}
.progra .btn-zoom:hover{background:#1a73e8}

/* Jueces Ausentes */
.progra .jueces-container{background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.08);padding:25px;margin-top:10px}
.progra .jueces-title{font-weight:700;margin-bottom:16px;display:flex;gap:10px;align-items:center;border-bottom:2px solid var(--borde);padding-bottom:10px}
.progra .jueces-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:15px}
.progra .juez-card{display:flex;align-items:center;padding:15px;background:var(--fondo-tabla);border-radius:8px;border-left:3px solid var(--juicio-oral);transition:.2s}
.progra .juez-card:hover{transform:translateX(5px);background:#e8f0fe}
.progra .juez-icon{width:45px;height:45px;background:var(--juicio-oral);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;margin-right:15px;flex-shrink:0}
.progra .juez-info h4{font-size:16px;font-weight:600;margin:0 0 4px;color:var(--texto)}
.progra .juez-info p{font-size:14px;color:var(--sec);font-weight:500;margin:0}

/*BTN excel */
.progra .fecha-toolbar .btn-excel{
  background:#10b981;           /* verde */
  color:#fff;
  border:1px solid #10b981;
}
.progra .fecha-toolbar .btn-excel:hover{
  background:#059669;
  border-color:#059669;
}

/* Responsive */
@media(max-width:768px){
  .progra .card-header{font-size:15px}
  .progra .tabla-juicio{font-size:0.9em}
}
@media(max-width:640px){
  .progra .meta-seccion{display:block;margin-left:0;margin-top:4px;font-size:.8em}
}
</style>

{{-- Volver + Título --}}
<div class="grid gap-6 pt-5 mb-6 lg:grid-cols-1 lg:gap-8">
  <div class="absolute top-4 left-4">
    <a href="{{ url('/') }}" class="bg-white text-black font-semibold py-1 px-3 rounded-md shadow-md flex items-center text-sm hover:bg-blue-700 hover:text-white transition">
      <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"></path></svg>
      INICIO
    </a>
  </div>

  <div class="flex justify-center items-start gap-4 rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] w-full">
    <div class="flex size-12 shrink-0 items-center justify-center rounded-full sm:size-16">
      <svg fill="#FFFFFF" viewBox="-2.4 -2.4 28.80 28.80" role="img" xmlns="http://www.w3.org/2000/svg" stroke="#FFFFFF" stroke-width="0.00024">
        <rect x="-2.4" y="-2.4" width="28.8" height="28.8" rx="14.4" fill="#2D8CFF"></rect>
        <path d="M4.587 13.63l-.27-.012H1.89l3.235-3.235-.013-.27a.815.815 0 0 0-.795-.795l-.27-.013H.004l.014.27c.034.438.353.77.794.795l.27.013H3.51L.273 13.618l.014.269c.015.433.362.78.795.796l.27.013h4.044l-.014-.27c-.036-.443-.35-.767-.795-.795zm3.237-4.325H7.82a2.695 2.695 0 1 0 .003 0zm1.141 3.839a1.618 1.618 0 1 1-2.288-2.287 1.618 1.618 0 0 1 2.288 2.287zm12.872-3.838a2.157 2.157 0 0 0-1.615.729 2.152 2.152 0 0 0-1.618-.731 2.147 2.147 0 0 0-1.208.37c-.21-.233-.68-.37-.948-.37v5.392l.27-.013c.45-.03.777-.349.795-.796l.013-.27V11.73l.014-.27c.01-.202.04-.382.132-.54a1.078 1.078 0 0 1 1.473-.393 1.078 1.078 0 0 1 .392.392c.093.16.12.339.132.54l.014.271v1.887l.013.269c.027.44.35.768.795.796l.27.013V11.46a2.157 2.157 0 0 0-2.16-2.155zm-10.26.788a2.696 2.696 0  1 0 3.81 3.813 2.696 2.696 0 0 0-3.81-3.813zm3.049 3.05a1.618 1.618 0 1 1-2.288-2.288 1.618 1.618 0 0 1 2.288 2.288z"/>
      </svg>
    </div>
    <div class="pt-3 sm:pt-5">
      <h2 class="text-xl font-semibold text-black">PROGRAMACIÓN DE AUDIENCIAS</h2>
    </div>
  </div>
</div>

{{-- Card principal --}}
<div class="grid gap-6 lg:grid-cols-1 lg:gap-8">
  <div class="flex flex-col gap-6 overflow-hidden rounded-lg bg-white p-6 shadow-[0px_14px_34px_0px_rgba(0,0,0,0.08)] ring-1 ring-white/[0.05] md:row-span-3 lg:p-10 lg:pb-10 w-full">
    <div class="progra">
      <div class="container">

        {{-- Encabezado con fecha y toolbar --}}
        <div class="header">
          <h1><i class="fa-solid fa-gavel"></i> Programación de Audiencias</h1>
          <h2><i class="fa-solid fa-landmark"></i> 4° Tribunal Oral en lo Penal de Santiago · {{ $fechaHuman }}</h2>

          @php
            $tz = 'America/Santiago';
            $hoyStr  = \Carbon\Carbon::now($tz)->toDateString();
            $prevStr = \Carbon\Carbon::parse($fecha, $tz)->subDay()->toDateString();
            $nextStr = \Carbon\Carbon::parse($fecha, $tz)->addDay()->toDateString();
          @endphp
          <div class="fecha-toolbar">
  {{-- Anterior --}}
  <a class="btn" href="{{ url()->current() }}?fecha={{ $prevStr }}">
    <i class="fa-solid fa-chevron-left"></i> Anterior
  </a>

  {{-- Selector + Ver --}}
  <form method="GET" action="{{ url()->current() }}" style="display:flex; gap:10px; align-items:center;">
    <input type="date" name="fecha" value="{{ $fecha }}" />
    <button type="submit" class="btn">
      <i class="fa-regular fa-calendar"></i> Ver
    </button>
  </form>

  {{-- Hoy --}}
  <a class="btn" href="{{ url()->current() }}?fecha={{ $hoyStr }}">
    <i class="fa-regular fa-clock"></i> Hoy
  </a>

  {{-- Siguiente --}}
  <a class="btn" href="{{ url()->current() }}?fecha={{ $nextStr }}">
    Siguiente <i class="fa-solid fa-chevron-right"></i>
  </a>

  {{-- Exportar Excel: toma la fecha seleccionada sin necesidad de presionar "Ver" --}}
  <form method="GET"
        action="{{ route('audiencias.export.diaria') }}"
        onsubmit="this.fecha.value = document.querySelector('.fecha-toolbar input[name=fecha]').value;"
        style="display:flex;">
    <input type="hidden" name="fecha" value="{{ $fecha }}">
    <button type="submit" class="btn btn-excel" title="Exportar a Excel">
      <i class="fa-solid fa-file-excel"></i> Exportar Excel
    </button>
  </form>
</div>
        </div>

        {{-- Turnos --}}
        <div class="turnos-container">
          <h2 class="turnos-title"><i class="fa-solid fa-calendar-days"></i> Turnos del Día</h2>
          <div class="turnos-grid">
            <div class="turno-card turno-1">
              <div class="etiqueta-turno"><i class="fa-solid fa-clock"></i> TURNO 1</div>
              <div class="turno-content">
                @if($turno1) <strong>{{ $turno1 }}</strong> @else <span class="sin-turno">NO HAY TURNO ASIGNADO</span> @endif
              </div>
            </div>
            <div class="turno-card turno-2">
              <div class="etiqueta-turno"><i class="fa-solid fa-clock"></i> TURNO 2</div>
              <div class="turno-content">
                @if($turno2) <strong>{{ $turno2 }}</strong> @else <span class="sin-turno">NO HAY TURNO ASIGNADO</span> @endif
              </div>
            </div>
            <div class="turno-card turno-3">
              <div class="etiqueta-turno"><i class="fa-solid fa-clock"></i> TURNO 3</div>
              <div class="turno-content">
                @if($turno3) <strong>{{ $turno3 }}</strong> @else <span class="sin-turno">NO HAY TURNO ASIGNADO</span> @endif
              </div>
            </div>
          </div>
        </div>

        {{-- ================= JUICIOS ================= --}}
        <div class="audiencias-container">
          <div class="seccion-audiencias">
            <div class="seccion-titulo titulo-juicio"><span class="icono">⚖️</span> Juicios Orales</div>

            @forelse($juicios as $j)
              <div class="audiencia-card">
                <div class="card-header header-juicio">{{ $j['encabezado'] }}</div>
                <div class="card-body">
                  <table class="tabla-juicio">
                    <tr>
                      <th>Delito</th>
                      <td colspan="3">{{ $j['delito'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Testigos</th>
                      <td class="texto-centrado">{{ $j['num_testigos'] ?? '—' }}</td>
                      <th>Peritos</th>
                      <td class="texto-centrado">{{ $j['num_peritos'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Jueces Inhabilitados</th>
                      <td class="texto-centrado">{{ $j['inhabil'] ?? '—' }}</td>
                      <th>Encargado Causa</th>
                      <td>{{ $j['encargado'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Juez Presidente</th>
                      <td>{{ $j['juez_p'] ?? '—' }}</td>
                      <th>Acta de sala</th>
                      <td>{{ $j['acta'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Juez Redactor</th>
                      <td>{{ $j['juez_r'] ?? '—' }}</td>
                      <th>Encargados de TTyPP</th>
                      <td>{{ $j['ttpp'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Juez Integrante</th>
                      <td>{{ $j['juez_i'] ?? '—' }}</td>
                      <th>Encargado TTyPP (Zoom)</th>
                      <td>{{ $j['ttpp_zoom'] ?? '—' }}</td>
                    </tr>
                  </table>

                  {{-- ACUSADOS: <=4 normal / >4 compacto emparejado --}}
                  @if (!empty($j['acusados']))
                    <div class="acusados-section">
                      <div class="acusados-title">Acusados</div>
                      @php
                        $acusadosArr = array_values(is_array($j['acusados']) ? $j['acusados'] : []);
                        $totalAcusados = count($acusadosArr);
                      @endphp

                      @if ($totalAcusados <= 4)
                        <table class="tabla-juicio">
                          <tr>
                            <th>Acusado</th>
                            <th>Situación</th>
                            <th>Medidas Cautelares</th>
                            <th>Forma de Notificación</th>
                          </tr>
                          @foreach ($acusadosArr as $a)
                            <tr>
                              <td><strong>{{ $a['nombre'] ?? '—' }}</strong></td>
                              <td>@if(!empty($a['situacion']))<span class="tag situacion">{{ $a['situacion'] }}</span>@else — @endif</td>
                              <td>@if(!empty($a['medidas']))<span class="tag medidas">{{ $a['medidas'] }}</span>@else — @endif</td>
                              <td>@if(!empty($a['notificacion']))<span class="tag notificacion">{{ $a['notificacion'] }}</span>@else — @endif</td>
                            </tr>
                          @endforeach
                        </table>
                      @else
                        <table class="tabla-juicio">
                          <tr>
                            <th>Acusados</th><th>Situación</th><th>Acusados</th><th>Situación</th>
                          </tr>
                          @for ($i = 0; $i < $totalAcusados; $i += 2)
                            @php $a1 = $acusadosArr[$i] ?? null; $a2 = $acusadosArr[$i+1] ?? null; @endphp
                            <tr>
                              <td>@if($a1) <strong>{{ $a1['nombre'] ?? '—' }}</strong> @else — @endif</td>
                              <td>@if($a1 && !empty($a1['situacion']))<span class="tag situacion">{{ $a1['situacion'] }}</span>@else — @endif</td>
                              <td>@if($a2) <strong>{{ $a2['nombre'] ?? '—' }}</strong> @else — @endif</td>
                              <td>@if($a2 && !empty($a2['situacion']))<span class="tag situacion">{{ $a2['situacion'] }}</span>@else — @endif</td>
                            </tr>
                          @endfor
                        </table>
                      @endif
                    </div>
                  @endif
                </div>
              </div>
            @empty
              <p style="color:var(--sec);padding:16px">No hay Juicios Orales para esta fecha.</p>
            @endforelse
          </div>

          {{-- ================ LECTURAS ================ --}}
          <div class="seccion-audiencias">
            <div class="seccion-titulo titulo-lectura"><span class="icono">📄</span> Lecturas de Sentencia</div>

            @forelse($lecturas as $l)
              <div class="audiencia-card">
                <div class="card-header header-lectura">{{ $l['encabezado'] }}</div>
                <div class="card-body">
                  <table class="tabla-juicio">
                    <tr>
                      <th>Acta de Audiencia</th>
                      <td>{{ $l['acta'] ?? '—' }}</td>
                      <th>Encargado Causa</th>
                      <td colspan="3">{{ $l['encargado'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Juez Redactor</th>
                      <td>{{ $l['juez_r'] ?? '—' }}</td>
                      <td colspan="2">
                        <a href="https://zoom.us/j/96002644455?pwd=TVpxNG5td010ekRwV0RzZzdiRXVSQT09" class="btn-zoom" target="_blank" rel="noopener noreferrer">Unirse a Zoom</a>
                      </td>
                    </tr>
                  </table>

                  @if(!empty($l['acusados']))
                    <div class="acusados-section">
                      @php
                        $acusadosArr = array_values(is_array($l['acusados']) ? $l['acusados'] : []);
                        $total = count($acusadosArr);
                      @endphp

                      @if ($total <= 4)
                        <table class="tabla-juicio">
                          <tr><th>Acusados</th></tr>
                          @foreach($acusadosArr as $a)
                            <tr><td>{{ $a['nombre'] ?? '—' }}</td></tr>
                          @endforeach
                        </table>
                      @else
                        <table class="tabla-juicio">
                          <tr><th>Acusados</th><th>Acusados</th></tr>
                          @for ($i = 0; $i < $total; $i += 2)
                            @php $a1 = $acusadosArr[$i] ?? null; $a2 = $acusadosArr[$i+1] ?? null; @endphp
                            <tr>
                              <td>@if($a1) {{ $a1['nombre'] ?? '—' }} @else — @endif</td>
                              <td>@if($a2) {{ $a2['nombre'] ?? '—' }} @else — @endif</td>
                            </tr>
                          @endfor
                        </table>
                      @endif
                    </div>
                  @endif
                </div>
              </div>
            @empty
              <p style="color:var(--sec);padding:16px">No hay Lecturas de Sentencia para esta fecha.</p>
            @endforelse
          </div>

          {{-- ================ CORTAS ================ --}}
          <div class="seccion-audiencias">
            <div class="seccion-titulo titulo-audiencia">
              <span class="icono">⏱️</span> Audiencias Cortas
              @if($anfitrionesCortas->isNotEmpty())
                <span class="meta-seccion">
                  · Anfitrión/a {{ $anfitrionesCortas->count()>1 ? 'es' : '' }}:
                  {{ $anfitrionesCortas->take(3)->join(', ') }}
                  @if($anfitrionesCortas->count() > 3)
                    +{{ $anfitrionesCortas->count() - 3 }}
                  @endif
                </span>
              @endif
            </div>

            @forelse($cortas as $c)
              <div class="audiencia-card">
                <div class="card-header header-audiencia">{{ $c['encabezado'] }}</div>
                <div class="card-body">
                  <table class="tabla-juicio">
                    <tr>
                      <th>Juez Presidente</th><td>{{ $c['juez_p'] ?? '—' }}</td>
                      <th>Acta de Audiencia</th><td>{{ $c['acta'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Juez Redactor</th><td>{{ $c['juez_r'] ?? '—' }}</td>
                      <th>Encargado Causa</th><td>{{ $c['encargado'] ?? '—' }}</td>
                    </tr>
                    <tr>
                      <th>Juez Integrante</th><td>{{ $c['juez_i'] ?? '—' }}</td>
                      <td colspan="2">
                        <a href="https://zoom.us/j/94505473191?pwd=ZWJYa0ZWSG1naXlNK3V3T2pZNjZVdz09" class="btn-zoom" target="_blank" rel="noopener noreferrer">Unirse a Zoom</a>
                      </td>
                    </tr>
                  </table>

                  @if(!empty($c['acusados']))
                    <div class="acusados-section">
                      @php
                        $acusadosArr = array_values(is_array($c['acusados']) ? $c['acusados'] : []);
                        $totalAcusados = count($acusadosArr);
                      @endphp

                      @if ($totalAcusados <= 4)
                        <table class="tabla-juicio">
                          <tr><th>Acusados</th><th>Situación de Libertad</th></tr>
                          @foreach($acusadosArr as $a)
                            <tr>
                              <td>{{ $a['nombre'] ?? '—' }}</td>
                              <td>@if(!empty($a['situacion']))<span class="tag situacion">{{ $a['situacion'] }}</span>@else — @endif</td>
                            </tr>
                          @endforeach
                        </table>
                      @else
                        <table class="tabla-juicio">
                          <tr><th>Acusados</th><th>Situación</th><th>Acusados</th><th>Situación</th></tr>
                          @for ($i = 0; $i < $totalAcusados; $i += 2)
                            @php $a1 = $acusadosArr[$i] ?? null; $a2 = $acusadosArr[$i+1] ?? null; @endphp
                            <tr>
                              <td>@if($a1) {{ $a1['nombre'] ?? '—' }} @else — @endif</td>
                              <td>@if($a1 && !empty($a1['situacion']))<span class="tag situacion">{{ $a1['situacion'] }}</span>@else — @endif</td>
                              <td>@if($a2) {{ $a2['nombre'] ?? '—' }} @else — @endif</td>
                              <td>@if($a2 && !empty($a2['situacion']))<span class="tag situacion">{{ $a2['situacion'] }}</span>@else — @endif</td>
                            </tr>
                          @endfor
                        </table>
                      @endif
                    </div>
                  @endif
                </div>
              </div>
            @empty
              <p style="color:var(--sec);padding:16px">No hay Audiencias Cortas para esta fecha.</p>
            @endforelse
          </div>
        </div>

        {{-- Jueces Ausentes --}}
        <div class="jueces-container">
          <h2 class="jueces-title"><i class="fa-solid fa-user-tie"></i> Jueces Ausentes</h2>
          @if(collect($juecesAusentes)->isEmpty())
            <p style="color:var(--sec)">Sin registros de ausencias para la fecha.</p>
          @else
            <div class="jueces-grid">
              @foreach($juecesAusentes as $j)
                <div class="juez-card">
                  <div class="juez-icon"><i class="fa-solid fa-user"></i></div>
                  <div class="juez-info">
                    <h4>{{ $j['nombre'] }}</h4>
                    <p>{{ $j['funcion'] }}</p>
                  </div>
                </div>
              @endforeach
            </div>
          @endif
        </div>

      </div>
    </div>
  </div>
</div>

@push('scripts')
{{-- scripts adicionales si luego agregas dinámica --}}
@endpush
</x-in-layout>
