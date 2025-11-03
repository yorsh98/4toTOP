@php
  // Recolecta anfitriones no vacíos y únicos desde las audiencias cortas
  $anfitrionesCortas = collect($cortas ?? [])
      ->pluck('anfitrion')
      ->filter(fn($v) => filled($v))
      ->unique()
      ->values();
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Programación de Audiencias — 4° TOP Santiago</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <style>        
        :root{
            --juicio-oral:#4285f4; --audiencia-corta:#34a853; --lectura:#9c27b0;
            --turno-1:#4285f4; --turno-2:#ea4335; --turno-3:#fbbc05;
            --fondo-tabla:#f8f9fa; --borde:#e0e0e0; --texto:#202124; --sec:#5f6368;
        }
        *{box-sizing:border-box}
        body{
            font-family:'Roboto','Open Sans',Arial,sans-serif;
            background:linear-gradient(135deg,#f5f7fa,#e4e7eb);
            color:var(--texto); line-height:1.6; padding:20px;
        }
        .container{max-width:1200px;margin:0 auto}

        /* Header */
        .header{
            background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08);
            padding:20px; margin-bottom:30px; position:relative; overflow:hidden;
        }
        .header::before{
            content:''; position:absolute; inset:0 0 auto 0; height:4px;
            background:linear-gradient(90deg,var(--juicio-oral),var(--audiencia-corta),var(--lectura));
        }
        .header h1{
            font-weight:700; font-size:32px; margin:8px 0 6px;
            display:flex; gap:12px; align-items:center; justify-content:center;
        }
        .header h2{
            color:var(--sec); font-weight:500; font-size:18px;
            display:flex; gap:8px; align-items:center; justify-content:center;
        }

        /* Turnos */
        .turnos-container{
            background:#fff; border-radius:12px; padding:20px;
            box-shadow:0 4px 12px rgba(0,0,0,.08); margin-bottom:30px;
        }
        .turnos-title{
            font-weight:700; text-align:center; margin-bottom:20px; font-size:20px;
            display:flex; gap:10px; align-items:center; justify-content:center;
        }
        .turnos-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(300px,1fr)); gap:20px}
        .turno-card{background:var(--fondo-tabla); border-radius:10px; padding:20px; border-left:4px solid}
        .turno-1{border-left-color:var(--turno-1)} .turno-2{border-left-color:var(--turno-2)} .turno-3{border-left-color:var(--turno-3)}
        .etiqueta-turno{display:inline-flex; align-items:center; padding:6px 16px; border-radius:20px; font-weight:700; color:#fff; gap:8px}
        .turno-1 .etiqueta-turno{background:var(--turno-1)} .turno-2 .etiqueta-turno{background:var(--turno-2)} .turno-3 .etiqueta-turno{background:var(--turno-3)}
        .turno-content{font-size:17px; font-weight:600; padding-left:10px; padding-top:10px }
        .sin-turno{color:#95a5a6; font-style:italic}

        /* Audiencias — FULL WIDTH por secciones */
        .audiencias-container{display:block; margin-bottom:40px}
        .seccion-audiencias{
            background:#fff; border-radius:12px; overflow:hidden;
            box-shadow:0 4px 12px rgba(0,0,0,.08); width:100%; margin-bottom:30px;
        }
        .seccion-titulo{
            font-size:20px; font-weight:700; color:#fff; padding:18px 20px;
            display:flex; gap:12px; align-items:center;
        }
        .titulo-juicio{background:var(--juicio-oral)} .titulo-lectura{background:var(--lectura)} .titulo-audiencia{background:var(--audiencia-corta)}

        /* Tarjeta de audiencia */
        .audiencia-card{background:#fff; border-radius:10px; overflow:hidden; border:1px solid var(--borde); margin:20px}
        .card-header{padding:10px 14px; color:#fff; font-weight:600}
        .header-juicio{background:var(--juicio-oral)} .header-lectura{background:var(--lectura)} .header-audiencia{background:var(--audiencia-corta)}
        .card-body{padding:0}

        /* Tablas internas (compactas) */
        .tabla-juicio{width:100%; border-collapse:collapse; font-size:0.92em}
        .tabla-juicio th,.tabla-juicio td{
            border:1px solid #ddd; padding:6px 8px; text-align:left; vertical-align:top; line-height:1.25;
        }
        .tabla-juicio th{background-color:#f2f2f2; font-weight:600; width:25%}
        .texto-centrado{text-align:center}

        /* Acusados — aún más compacto y tipografía levemente menor */
        .acusados-section{margin:15px 20px 20px; border-top:1px dashed #eee; padding-top:12px}
        .acusados-title{font-weight:700; color:#2c3e50; margin:0 0 10px}
        .acusados-section .tabla-juicio{font-size:0.85em}
        .acusados-section .tabla-juicio th,.acusados-section .tabla-juicio td{padding:5px 7px}
        .tag{display:inline-block; padding:2px 8px; border-radius:10px; font-size:.75em; font-weight:700}
        .tag.situacion{background:#e6f4ea; color:#137333}
        .tag.medidas{background:#fce8e6; color:#a50e0e}
        .tag.notificacion{background:#e8f0fe; color:#174ea6}

        /* Botón Zoom (compacto) */
        .btn-zoom{
            display:inline-block; background:#2d8cff; color:#fff;
            padding:6px 10px; border-radius:6px; text-decoration:none; font-weight:600; transition:background .2s;
        }
        .btn-zoom:hover{background:#1a73e8}

        /* Jueces Ausentes (cards) */
        .jueces-container{
            background:#fff; border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,.08);
            padding:25px; margin-top:10px;
        }
        .jueces-title{
            font-weight:700; margin-bottom:16px; display:flex; gap:10px; align-items:center;
            border-bottom:2px solid var(--borde); padding-bottom:10px;
        }
        .jueces-grid{display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:15px}
        .juez-card{
            display:flex; align-items:center; padding:15px; background:var(--fondo-tabla);
            border-radius:8px; border-left:3px solid var(--juicio-oral); transition:.2s;
        }
        .juez-card:hover{transform:translateX(5px); background:#e8f0fe}
        .juez-icon{
            width:45px; height:45px; background:var(--juicio-oral); border-radius:50%;
            display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; margin-right:15px; flex-shrink:0;
        }
        .juez-info h4{font-size:16px; font-weight:600; margin:0 0 4px; color:var(--texto)}
        .juez-info p{font-size:14px; color:var(--sec); font-weight:500; margin:0}

        @media(max-width:768px){
            .card-header{font-size:15px}
            .tabla-juicio{font-size:0.9em}
        }
        /* Badge sutil para anfitrión en el encabezado */
        .meta-anfitrion{
            font-weight:500;
            font-size:.85em;     /* un poco más pequeño */
            opacity:.9;
            margin-left:.5rem;   /* espacio tras el título */
            white-space:nowrap;  /* evita cortes raros */
        }

        /* Si prefieres que baje de línea en pantallas angostas */
        @media (max-width:640px){
        .meta-anfitrion{
            display:block;
            margin-left:0;
            margin-top:2px;
            opacity:.95;
        }
        }

        .meta-seccion{
            font-weight:500;
            font-size:.9em;          /* un poco más pequeño que el título */
            opacity:.9;              /* sutil */
            margin-left:.6rem;       /* espacio tras el texto del título */
            padding:2px 8px;
            border-radius:999px;
            background:rgba(255,255,255,.18); /* discreto sobre el fondo verde */
            color:#fff;
            white-space:nowrap;
        }
        @media (max-width:640px){
            .meta-seccion{display:block;margin-left:0;margin-top:4px}
        }

    </style>
</head>
<body>
<div class="container">
    <!-- Encabezado -->
    <div class="header">
        <h1><i class="fas fa-gavel"></i> Programación de Audiencias</h1>
        <h2><i class="fas fa-landmark"></i> 4° Tribunal Oral en lo Penal de Santiago · {{ $fechaHuman }}</h2>
    </div>

    <!-- Turnos -->
    <div class="turnos-container">
        <h2 class="turnos-title"><i class="fas fa-calendar-alt"></i> Turnos del Día</h2>
        <div class="turnos-grid">
            <div class="turno-card turno-1">
                <div class="etiqueta-turno"><i class="fas fa-clock"></i> TURNO 1</div>
                <div class="turno-content">
                    @if($turno1) <strong>{{ $turno1 }}</strong> @else <span class="sin-turno">NO HAY TURNO ASIGNADO</span> @endif
                </div>
            </div>
            <div class="turno-card turno-2">
                <div class="etiqueta-turno"><i class="fas fa-clock"></i> TURNO 2</div>
                <div class="turno-content">
                    @if($turno2) <strong>{{ $turno2 }}</strong> @else <span class="sin-turno">NO HAY TURNO ASIGNADO</span> @endif
                </div>
            </div>
            <div class="turno-card turno-3">
                <div class="etiqueta-turno"><i class="fas fa-clock"></i> TURNO 3</div>
                <div class="turno-content">
                    @if($turno3) <strong>{{ $turno3 }}</strong> @else <span class="sin-turno">NO HAY TURNO ASIGNADO</span> @endif
                </div>
            </div>
        </div>
    </div>

    <!-- AUDIENCIAS (FULL WIDTH) — ORDEN: JUICIOS → LECTURAS → CORTAS -->
    <div class="audiencias-container">
        <!-- Juicios Orales -->
        <div class="seccion-audiencias">
            <div class="seccion-titulo titulo-juicio">
                <span class="icono">⚖️</span> Juicios Orales
            </div>

            @forelse($juicios as $j)
                <div class="audiencia-card">
                    <div class="card-header header-juicio">
                        {{ $j['encabezado'] }}
                    </div>
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

                        @if(!empty($j['acusados']))
                            <div class="acusados-section">
                                <div class="acusados-title">Acusados</div>
                                <table class="tabla-juicio">
                                    <tr>
                                        <th>Acusados</th>
                                        <th>Situación de Libertad</th>
                                        <th>Medidas Cautelares</th>
                                        <th>Forma de Notificación</th>
                                    </tr>
                                    @foreach($j['acusados'] as $a)
                                        <tr>
                                            <td><strong>{{ $a['nombre'] ?? '—' }}</strong></td>
                                            <td>@if(!empty($a['situacion']))<span class="tag situacion">{{ $a['situacion'] }}</span>@else — @endif</td>
                                            <td>@if(!empty($a['medidas']))<span class="tag medidas">{{ $a['medidas'] }}</span>@else — @endif</td>
                                            <td>@if(!empty($a['notificacion']))<span class="tag notificacion">{{ $a['notificacion'] }}</span>@else — @endif</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:var(--sec);padding:16px">No hay Juicios Orales para esta fecha.</p>
            @endforelse
        </div>

        <!-- Lecturas de Sentencia -->
        <div class="seccion-audiencias">
            <div class="seccion-titulo titulo-lectura">
                <span class="icono">📄</span> Lecturas de Sentencia
            </div>

            @forelse($lecturas as $l)
                <div class="audiencia-card">
                    <div class="card-header header-lectura">
                        {{ $l['encabezado'] }}
                    </div>
                    <div class="card-body">
                        <table class="tabla-juicio">
                            <tr>
                                <th>Juez Redactor</th>
                                <td>{{ $l['juez_r'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Acta de Audiencia</th>
                                <td>{{ $l['acta'] ?? '—' }}</td>
                                <th>Encargado Causa</th>
                                <td colspan="3">{{ $l['encargado'] ?? '—' }}</td>                                                                
                            </tr>
                        </table>

                        @if(!empty($l['acusados']))
                            <div class="acusados-section">
                                <table class="tabla-juicio">
                                    <tr><th>Acusados</th></tr>
                                    @foreach($l['acusados'] as $a)
                                        <tr><td>{{ $a['nombre'] ?? '—' }}</td></tr>
                                    @endforeach
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:var(--sec);padding:16px">No hay Lecturas de Sentencia para esta fecha.</p>
            @endforelse
        </div>

        <!-- Audiencias Cortas -->
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
                    <div class="card-header header-audiencia">
                        {{ $c['encabezado'] }}                        
                    </div>
                    <div class="card-body">
                        <table class="tabla-juicio">                            
                            <tr>
                                <th>Juez Presidente</th>
                                <td>{{ $c['juez_p'] ?? '—' }}</td>
                                <th>Acta de Audiencia</th>
                                <td>{{ $c['acta'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Juez Redactor</th>
                                <td>{{ $c['juez_r'] ?? '—' }}</td>
                                <th>Encargado Causa</th>
                                <td>{{ $c['encargado'] ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th>Juez Integrante</th>
                                <td>{{ $c['juez_i'] ?? '—' }}</td>
                                <td colspan="2">
                                    <a href="https://zoom.us/j/94505473191?pwd=ZWJYa0ZWSG1naXlNK3V3T2pZNjZVdz09"
                                    class="btn-zoom" target="_blank" rel="noopener noreferrer">
                                    Unirse a Zoom
                                    </a>
                                </td>
                            </tr>
                        </table>

                        @if(!empty($c['acusados']))
                            <div class="acusados-section">
                                <table class="tabla-juicio">
                                    <tr>
                                        <th>Acusados</th>
                                        <th>Situación de Libertad</th>
                                    </tr>
                                    @foreach($c['acusados'] as $a)
                                        <tr>
                                            <td>{{ $a['nombre'] ?? '—' }}</td>
                                            <td>@if(!empty($a['situacion']))<span class="tag situacion">{{ $a['situacion'] }}</span>@else — @endif</td>
                                        </tr>
                                    @endforeach
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:var(--sec);padding:16px">No hay Audiencias Cortas para esta fecha.</p>
            @endforelse
        </div>
    </div>

    <!-- Jueces Ausentes (cards) -->
    <div class="jueces-container">
        <h2 class="jueces-title"><i class="fas fa-user-tie"></i> Jueces Ausentes </h2>
        @if(collect($juecesAusentes)->isEmpty())
            <p style="color:var(--sec)">Sin registros de ausencias para la fecha.</p>
        @else
            <div class="jueces-grid">
                @foreach($juecesAusentes as $j)
                    <div class="juez-card">
                        <div class="juez-icon"><i class="fas fa-user"></i></div>
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
</body>
</html>
