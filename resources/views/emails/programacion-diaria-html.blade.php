<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta name="x-apple-disable-message-reformatting">
  <title>Programación de Audiencias</title>
  <!--[if mso]>
    <xml>
      <o:OfficeDocumentSettings>
        <o:AllowPNG/>
        <o:PixelsPerInch>96</o:PixelsPerInch>
      </o:OfficeDocumentSettings>
    </xml>
  <![endif]-->
  <style>
    /* Responsive básico (Gmail/Apple Mail). Outlook ignora @media. */
    @media only screen and (max-width: 768px){
      .container{ width:100% !important; }
      .px-outer{ padding-left:16px !important; padding-right:16px !important; }
      .w-inner{ width:100% !important; }
      .td-gutter{ width:16px !important; }
    }
  </style>
  <!--[if mso]>
    <style>
      .has-radius { border-radius:0 !important; }
      .no-radius  { border-radius:0 !important; }
      .card       { border-radius:0 !important; overflow:visible !important; }
      .mso-center { text-align:center !important; }
      body, td, th, p, a, h1, h2, h3 { font-family: Arial, Helvetica, sans-serif !important; }
    </style>
  <![endif]-->
</head>
<body style="margin:0;padding:0;background:#f4f4f5;">

@php
  /** Helpers robustos para leer campos desde array/objeto **/
  $getField = function ($item, array $keys) {
      foreach ($keys as $k) {
          if (is_array($item) && array_key_exists($k, $item)) {
              $v = is_string($item[$k]) ? trim($item[$k]) : $item[$k];
              if ($v !== null && $v !== '') return $v;
          }
          if (is_object($item) && isset($item->{$k})) {
              $v = is_string($item->{$k}) ? trim($item->{$k}) : $item->{$k};
              if ($v !== null && $v !== '') return $v;
          }
      }
      return null;
  };
  $getNombre = fn($a) => $getField($a, ['nombre','acusado','imputado','name']);
  $getSit    = fn($a) => $getField($a, ['situacion','situación','situacion_libertad','situacion_de_libertad','estado','status']);
  $getMed    = fn($a) => $getField($a, ['medidas','medidas_cautelares','cautelares','medida_cautelar']);
  $getNot    = fn($a) => $getField($a, ['notificacion','forma_notificacion','forma_de_notificacion','notificar']);

  /** Divide lista en dos para pintar A|S|A|S */
  $split4Cols = function (?array $arr) {
      $arr = array_values($arr ?? []);
      $n = count($arr);
      $leftCount = (int) ceil(max($n,0) / 2);
      return [ array_slice($arr, 0, $leftCount), array_slice($arr, $leftCount) ];
  };

  /* Estilos inline reutilizables */
  $pill  = 'display:inline-block;background:#4285f4;color:#fff;padding:8px 16px;border-radius:16px;font-weight:bold;font-size:12px;';
  $box   = 'background:#f8f9fa;border:1px solid #e0e0e0;border-radius:8px;padding:14px;margin-bottom:14px;';
  $muted = 'color:#9aa0a6;font-style:italic;';
  $secTitle = function($bg){ return "margin:0;padding:14px 16px;background:$bg;color:#fff;font-weight:bold;border-radius:6px;font-size:16px;"; };
  $card     = "border:1px solid #e0e0e0;border-radius:10px;overflow:hidden;";
  $head     = function($bg){ return "margin:0;padding:14px 16px;background:$bg;color:#fff;font-weight:bold;font-size:15px;"; };
  $tbl      = "width:100%;border-collapse:collapse;font-size:14px;mso-table-lspace:0pt;mso-table-rspace:0pt;";
  $th       = "background:#f2f2f2;border:1px solid #ddd;padding:8px;text-align:left;vertical-align:top;";
  $td       = "border:1px solid #ddd;padding:8px;text-align:left;vertical-align:top;";
  $tag      = "display:inline-block;padding:3px 8px;border-radius:10px;font-size:12px;font-weight:bold;";
  $tagSit   = $tag."background:#e6f4ea;color:#137333;";
@endphp

<!-- Lienzo gris + contenedor blanco centrado -->
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;mso-line-height-rule:exactly;mso-table-lspace:0pt;mso-table-rspace:0pt;">
  <tr>
    <td align="center" class="px-outer" style="padding:28px 0;">

      <!-- Ghost table MSO para fijar 800px en Outlook -->
      <!--[if mso]>
      <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="800" style="mso-table-lspace:0pt;mso-table-rspace:0pt;">
        <tr><td>
      <![endif]-->

      <table role="presentation" cellpadding="0" cellspacing="0" width="800" class="container has-radius"
             style="width:800px;max-width:800px;background:#ffffff;border:1px solid #e7e7e9;border-radius:10px;overflow:hidden;
                    font-family:Arial,Helvetica,sans-serif;color:#202124;mso-table-lspace:0pt;mso-table-rspace:0pt;">

        <!-- Barra superior -->
        <tr><td style="height:4px;background:#4285f4;font-size:0;line-height:0;mso-line-height-rule:exactly;">&nbsp;</td></tr>

        <!-- HEADER (dentro de gutters fijos 32px/32px) -->
        <tr>
          <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="td-gutter" width="32" style="width:32px;"></td>
                <td>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="w-inner" style="width:736px;max-width:736px;">
                    <tr>
                      <td style="padding:26px 0 18px 0;border-bottom:1px solid #e0e0e0;">
                        <!-- Título para Outlook (sin emoji) -->
                        <!--[if mso]>
                          <h1 class="mso-center" style="margin:0;font-size:28px;line-height:34px;font-family:Arial,Helvetica,sans-serif;">
                            Programación de Audiencias
                          </h1>
                          <p class="mso-center" style="margin:10px 0 0 0;font-size:16px;line-height:22px;color:#5f6368;font-family:Arial,Helvetica,sans-serif;">
                            4° Tribunal Oral en lo Penal de Santiago · {{ $fechaHuman }}
                          </p>
                        <![endif]-->
                        <!-- Para el resto de clientes (con emoji) -->
                        <!--[if !mso]><!-- -->
                          <h1 style="margin:0;font-size:28px;line-height:32px;text-align:center;mso-line-height-rule:exactly;">
                            ⚖️ Programación de Audiencias
                          </h1>
                          <p style="margin:12px 0 0 0;font-size:16px;line-height:22px;color:#5f6368;text-align:center;mso-line-height-rule:exactly;">
                            🏛️ 4° Tribunal Oral en lo Penal de Santiago · {{ $fechaHuman }}
                          </p>
                        <!--<![endif]-->
                      </td>
                    </tr>
                  </table>
                </td>
                <td class="td-gutter" width="32" style="width:32px;"></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- TURNOS (con gutters) -->
        <tr>
          <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="td-gutter" width="32" style="width:32px;"></td>
                <td>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="w-inner" style="width:736px;max-width:736px;">
                    <tr>
                      <td style="padding:22px 0;">
                        <!-- Título sección: condicional emoji -->
                        <!--[if mso]>
                          <h2 style="margin:0 0 14px 0;font-size:20px;line-height:24px;text-align:center;">Turnos del día</h2>
                        <![endif]-->
                        <!--[if !mso]><!-- -->
                          <h2 style="margin:0 0 14px 0;font-size:20px;line-height:24px;text-align:center;">🗓️ Turnos del día</h2>
                        <!--<![endif]-->

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="mso-table-lspace:0pt;mso-table-rspace:0pt;border-collapse:separate;">
                          <tr>
                            <td class="card" style="{{ $box }}">
                              <span style="{{ $pill }}">TURNO 1</span>
                              <div style="margin-top:8px;font-size:14px;line-height:20px;mso-line-height-rule:exactly;">
                                @if(!empty($turno1)) <strong>{{ $turno1 }}</strong> @else <span style="{{ $muted }}">NO HAY TURNO ASIGNADO</span> @endif
                              </div>
                            </td>
                          </tr>
                          <tr>
                            <td class="card" style="{{ $box }}">
                              <span style="{{ str_replace('#4285f4','#ea4335',$pill) }}">TURNO 2</span>
                              <div style="margin-top:8px;font-size:14px;line-height:20px;mso-line-height-rule:exactly;">
                                @if(!empty($turno2)) <strong>{{ $turno2 }}</strong> @else <span style="{{ $muted }}">NO HAY TURNO ASIGNADO</span> @endif
                              </div>
                            </td>
                          </tr>
                          <tr>
                            <td class="card" style="{{ $box }}">
                              <span style="{{ str_replace('#4285f4','#fbbc05',$pill) }}">TURNO 3</span>
                              <div style="margin-top:8px;font-size:14px;line-height:20px;mso-line-height-rule:exactly;">
                                @if(!empty($turno3)) <strong>{{ $turno3 }}</strong> @else <span style="{{ $muted }}">NO HAY TURNO ASIGNADO</span> @endif
                              </div>
                            </td>
                          </tr>
                        </table>

                      </td>
                    </tr>
                  </table>
                </td>
                <td class="td-gutter" width="32" style="width:32px;"></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ===== JUICIOS ORALES ===== (con gutters) -->
        <tr>
          <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="td-gutter" width="32" style="width:32px;"></td>
                <td>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="w-inner" style="width:736px;max-width:736px;">
                    <tr>
                      <td style="{{ $secTitle('#4285f4') }}"><!--[if mso]>Juicios Orales<![endif]--><!--[if !mso]><!-- -->⚖️ Juicios Orales<!--<![endif]--></td>
                    </tr>
                    <tr>
                      <td style="padding:12px 0 0 0;">
                        @forelse($juicios as $j)
                          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card" style="{{ $card }} margin-top:16px;">
                            <tr><td style="{{ $head('#4285f4') }}">{{ $j['encabezado'] }}</td></tr>
                            <tr>
                              <td style="padding:0 16px 16px 16px;">
                                <table role="presentation" style="{{ $tbl }}">
                                  <tr>
                                    <th style="{{ $th }}">Delito</th>
                                    <td colspan="3" style="{{ $td }}">{{ $j['delito'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Testigos</th>
                                    <td style="{{ $td }}">{{ $j['num_testigos'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Peritos</th>
                                    <td style="{{ $td }}">{{ $j['num_peritos'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Jueces Inhabilitados</th>
                                    <td style="{{ $td }}">{{ $j['inhabil'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Encargado Causa</th>
                                    <td style="{{ $td }}">{{ $j['encargado'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Juez Presidente</th>
                                    <td style="{{ $td }}">{{ $j['juez_p'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Acta de sala</th>
                                    <td style="{{ $td }}">{{ $j['acta'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Juez Redactor</th>
                                    <td style="{{ $td }}">{{ $j['juez_r'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Encargados de TTyPP</th>
                                    <td style="{{ $td }}">{{ $j['ttpp'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Juez Integrante</th>
                                    <td style="{{ $td }}">{{ $j['juez_i'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Encargado TTyPP (Zoom)</th>
                                    <td style="{{ $td }}">{{ $j['ttpp_zoom'] ?? '—' }}</td>
                                  </tr>
                                </table>

                                @php $acus = is_array($j['acusados'] ?? null) ? $j['acusados'] : []; $n = count($acus); @endphp

                                @if($n > 4)
                                  @php
                                    [$left,$right] = $split4Cols($acus);
                                    $rows = max(count($left), count($right));
                                  @endphp
                                  <div style="margin-top:12px;">
                                    <div style="font-weight:bold;margin:0 0 8px 0;">Acusados</div>
                                    <table role="presentation" style="{{ $tbl }}">
                                      <tr>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                      </tr>
                                      @for($i=0;$i<$rows;$i++)
                                        @php
                                          $L=$left[$i]??null; $R=$right[$i]??null;
                                          $Ln = $L ? ($getNombre($L) ?? '—') : '—';
                                          $Rn = $R ? ($getNombre($R) ?? '—') : '—';
                                          $Ls = $L ? $getSit($L) : null;
                                          $Rs = $R ? $getSit($R) : null;
                                        @endphp
                                        <tr>
                                          <td style="{{ $td }}">{{ $Ln }}</td>
                                          <td style="{{ $td }}">@if($Ls)<span style="{{ $tagSit }}">{{ $Ls }}</span>@else — @endif</td>
                                          <td style="{{ $td }}">{{ $Rn }}</td>
                                          <td style="{{ $td }}">@if($Rs)<span style="{{ $tagSit }}">{{ $Rs }}</span>@else — @endif</td>
                                        </tr>
                                      @endfor
                                    </table>
                                  </div>
                                @elseif($n > 0)
                                  <div style="margin-top:12px;">
                                    <div style="font-weight:bold;margin:0 0 8px 0;">Acusados</div>
                                    <table role="presentation" style="{{ $tbl }}">
                                      <tr>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación de Libertad</th>
                                        <th style="{{ $th }}">Medidas Cautelares</th>
                                        <th style="{{ $th }}">Forma de Notificación</th>
                                      </tr>
                                      @foreach($acus as $a)
                                        @php
                                          $Nm = $getNombre($a) ?? '—';
                                          $Si = $getSit($a);
                                          $Me = $getMed($a);
                                          $No = $getNot($a);
                                        @endphp
                                        <tr>
                                          <td style="{{ $td }}">{{ $Nm }}</td>
                                          <td style="{{ $td }}">@if($Si)<span style="{{ $tagSit }}">{{ $Si }}</span>@else — @endif</td>
                                          <td style="{{ $td }}">@if($Me)<span>{{ $Me }}</span>@else — @endif</td>
                                          <td style="{{ $td }}">@if($No)<span>{{ $No }}</span>@else — @endif</td>
                                        </tr>
                                      @endforeach
                                    </table>
                                  </div>
                                @endif
                              </td>
                            </tr>
                          </table>
                        @empty
                          <p style="margin:8px 0 0 0;color:#5f6368;">No hay Juicios Orales para esta fecha.</p>
                        @endforelse
                      </td>
                    </tr>
                  </table>
                </td>
                <td class="td-gutter" width="32" style="width:32px;"></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ===== LECTURAS ===== (con gutters + ESPACIADOR SUPERIOR) -->
        <tr>
          <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="td-gutter" width="32" style="width:32px;"></td>
                <td>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="w-inner" style="width:736px;max-width:736px;">
                    <!-- ESPACIADOR superior -->
                    <tr>
                      <td style="height:20px;line-height:20px;font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                      <td style="{{ $secTitle('#9c27b0') }}"><!--[if mso]>Lecturas de Sentencia<![endif]--><!--[if !mso]><!-- -->📄 Lecturas de Sentencia<!--<![endif]--></td>
                    </tr>
                    <tr>
                      <td style="padding:12px 0 0 0;">
                        @forelse($lecturas as $l)
                          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card" style="{{ $card }} margin-top:16px;">
                            <tr><td style="{{ $head('#9c27b0') }}">{{ $l['encabezado'] }}</td></tr>
                            <tr>
                              <td style="padding:0 16px 16px 16px;">
                                <table role="presentation" style="{{ $tbl }}">
                                  <tr>
                                    <th style="{{ $th }}">Juez Redactor</th>
                                    <td style="{{ $td }}">{{ $l['juez_r'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Encargado Causa</th>
                                    <td style="{{ $td }}">{{ $l['encargado'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Acta de Audiencia</th>
                                    <td style="{{ $td }}">{{ $l['acta'] ?? '—' }}</td>
                                    <td colspan="2" style="{{ $td }};text-align:left;">
                                      <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="https://zoom.us/j/96002644455?pwd=TVpxNG5td010ekRwV0RzZzdiRXVSQT09"
                                          style="height:32px;v-text-anchor:middle;width:180px;" arcsize="10%" stroke="f" fillcolor="#2d8cff">
                                          <w:anchorlock/><center style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;">Unirse a Zoom</center>
                                        </v:roundrect>
                                      <![endif]-->
                                      <!--[if !mso]><!-- -->
                                        <a href="https://zoom.us/j/96002644455?pwd=TVpxNG5td010ekRwV0RzZzdiRXVSQT09"
                                           style="display:inline-block;background:#2d8cff;color:#fff;padding:9px 16px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:13px;line-height:16px;">
                                          Unirse a Zoom
                                        </a>
                                      <!--<![endif]-->
                                    </td>
                                  </tr>
                                </table>

                                @php $acus = is_array($l['acusados'] ?? null) ? $l['acusados'] : []; $n = count($acus); @endphp

                                @if($n > 4)
                                  @php
                                    [$left,$right] = $split4Cols($acus);
                                    $rows = max(count($left), count($right));
                                  @endphp
                                  <div style="margin-top:12px;">
                                    <div style="font-weight:bold;margin:0 0 8px 0;">Acusados</div>
                                    <table role="presentation" style="{{ $tbl }}">
                                      <tr>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                      </tr>
                                      @for($i=0;$i<$rows;$i++)
                                        @php
                                          $L=$left[$i]??null; $R=$right[$i]??null;
                                          $Ln = $L ? ($getNombre($L) ?? '—') : '—';
                                          $Rn = $R ? ($getNombre($R) ?? '—') : '—';
                                          $Ls = $L ? $getSit($L) : null;
                                          $Rs = $R ? $getSit($R) : null;
                                        @endphp
                                        <tr>
                                          <td style="{{ $td }}">{{ $Ln }}</td>
                                          <td style="{{ $td }}">@if($Ls)<span style="{{ $tagSit }}">{{ $Ls }}</span>@else — @endif</td>
                                          <td style="{{ $td }}">{{ $Rn }}</td>
                                          <td style="{{ $td }}">@if($Rs)<span style="{{ $tagSit }}">{{ $Rs }}</span>@else — @endif</td>
                                        </tr>
                                      @endfor
                                    </table>
                                  </div>
                                @elseif($n > 0)
                                  <div style="margin-top:12px;">
                                    <div style="font-weight:bold;margin:0 0 8px 0;">Acusados</div>
                                    <table role="presentation" style="{{ $tbl }}">
                                      <tr>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                      </tr>
                                      @foreach($acus as $a)
                                        @php
                                          $Nm = $getNombre($a) ?? '—';
                                          $Si = $getSit($a);
                                        @endphp
                                        <tr>
                                          <td style="{{ $td }}">{{ $Nm }}</td>
                                          <td style="{{ $td }}">@if($Si)<span style="{{ $tagSit }}">{{ $Si }}</span>@else — @endif</td>
                                        </tr>
                                      @endforeach
                                    </table>
                                  </div>
                                @endif
                              </td>
                            </tr>
                          </table>
                        @empty
                          <p style="margin:8px 0 0 0;color:#5f6368;">No hay Lecturas de Sentencia para esta fecha.</p>
                        @endforelse
                      </td>
                    </tr>
                  </table>
                </td>
                <td class="td-gutter" width="32" style="width:32px;"></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ===== CORTAS ===== (con gutters + ESPACIADOR SUPERIOR) -->
        <tr>
          <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="td-gutter" width="32" style="width:32px;"></td>
                <td>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="w-inner" style="width:736px;max-width:736px;">
                    <!-- ESPACIADOR superior -->
                    <tr>
                      <td style="height:20px;line-height:20px;font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                      <td style="{{ $secTitle('#34a853') }}"><!--[if mso]>Audiencias Cortas<![endif]--><!--[if !mso]><!-- -->⏱️ Audiencias Cortas<!--<![endif]--></td>
                    </tr>

                    @php
                      $anfitrionesCortas = collect($cortas ?? [])
                        ->pluck('anfitrion')->filter(fn($v)=>filled($v))->unique()->values();
                    @endphp

                    @if($anfitrionesCortas->isNotEmpty())
                      <tr>
                        <td>
                          <p style="margin:10px 0 0 0;font-size:13px;line-height:18px;color:#1f5e2d;mso-line-height-rule:exactly;">
                            · Anfitrión/a {{ $anfitrionesCortas->count()>1 ? 'es' : '' }}:
                            {{ $anfitrionesCortas->take(3)->join(', ') }}@if($anfitrionesCortas->count()>3) +{{ $anfitrionesCortas->count()-3 }}@endif
                          </p>
                        </td>
                      </tr>
                    @endif

                    <tr>
                      <td style="padding:12px 0 0 0;">
                        @forelse($cortas as $c)
                          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="card" style="{{ $card }} margin-top:16px;">
                            <tr><td style="{{ $head('#34a853') }}">{{ $c['encabezado'] }}</td></tr>
                            <tr>
                              <td style="padding:0 16px 16px 16px;">
                                <table role="presentation" style="{{ $tbl }}">
                                  <tr>
                                    <th style="{{ $th }}">Juez Presidente</th>
                                    <td style="{{ $td }}">{{ $c['juez_p'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Acta de Audiencia</th>
                                    <td style="{{ $td }}">{{ $c['acta'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Juez Redactor</th>
                                    <td style="{{ $td }}">{{ $c['juez_r'] ?? '—' }}</td>
                                    <th style="{{ $th }}">Encargado Causa</th>
                                    <td style="{{ $td }}">{{ $c['encargado'] ?? '—' }}</td>
                                  </tr>
                                  <tr>
                                    <th style="{{ $th }}">Juez Integrante</th>
                                    <td style="{{ $td }}">{{ $c['juez_i'] ?? '—' }}</td>
                                    <td colspan="2" style="{{ $td }};text-align:left;">
                                      <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="https://zoom.us/j/94505473191?pwd=ZWJYa0ZWSG1naXlNK3V3T2pZNjZVdz09"
                                          style="height:32px;v-text-anchor:middle;width:180px;" arcsize="10%" stroke="f" fillcolor="#2d8cff">
                                          <w:anchorlock/><center style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:bold;">Unirse a Zoom</center>
                                        </v:roundrect>
                                      <![endif]-->
                                      <!--[if !mso]><!-- -->
                                        <a href="https://zoom.us/j/94505473191?pwd=ZWJYa0ZWSG1naXlNK3V3T2pZNjZVdz09"
                                           style="display:inline-block;background:#2d8cff;color:#fff;padding:9px 16px;border-radius:6px;text-decoration:none;font-weight:bold;font-size:13px;line-height:16px;">
                                          Unirse a Zoom
                                        </a>
                                      <!--<![endif]-->
                                    </td>
                                  </tr>
                                </table>

                                @php $acus = is_array($c['acusados'] ?? null) ? $c['acusados'] : []; $n = count($acus); @endphp

                                @if($n > 4)
                                  @php
                                    [$left,$right] = $split4Cols($acus);
                                    $rows = max(count($left), count($right));
                                  @endphp
                                  <div style="margin-top:12px;">
                                    <div style="font-weight:bold;margin:0 0 8px 0;">Acusados</div>
                                    <table role="presentation" style="{{ $tbl }}">
                                      <tr>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación</th>
                                      </tr>
                                      @for($i=0;$i<$rows;$i++)
                                        @php
                                          $L=$left[$i]??null; $R=$right[$i]??null;
                                          $Ln = $L ? ($getNombre($L) ?? '—') : '—';
                                          $Rn = $R ? ($getNombre($R) ?? '—') : '—';
                                          $Ls = $L ? $getSit($L) : null;
                                          $Rs = $R ? $getSit($R) : null;
                                        @endphp
                                        <tr>
                                          <td style="{{ $td }}">{{ $Ln }}</td>
                                          <td style="{{ $td }}">@if($Ls)<span style="{{ $tagSit }}">{{ $Ls }}</span>@else — @endif</td>
                                          <td style="{{ $td }}">{{ $Rn }}</td>
                                          <td style="{{ $td }}">@if($Rs)<span style="{{ $tagSit }}">{{ $Rs }}</span>@else — @endif</td>
                                        </tr>
                                      @endfor
                                    </table>
                                  </div>
                                @elseif($n > 0)
                                  <div style="margin-top:12px;">
                                    <div style="font-weight:bold;margin:0 0 8px 0;">Acusados</div>
                                    <table role="presentation" style="{{ $tbl }}">
                                      <tr>
                                        <th style="{{ $th }}">Acusados</th>
                                        <th style="{{ $th }}">Situación de Libertad</th>
                                      </tr>
                                      @foreach($acus as $a)
                                        @php
                                          $Nm = $getNombre($a) ?? '—';
                                          $Si = $getSit($a);
                                        @endphp
                                        <tr>
                                          <td style="{{ $td }}">{{ $Nm }}</td>
                                          <td style="{{ $td }}">@if($Si)<span style="{{ $tagSit }}">{{ $Si }}</span>@else — @endif</td>
                                        </tr>
                                      @endforeach
                                    </table>
                                  </div>
                                @endif
                              </td>
                            </tr>
                          </table>
                        @empty
                          <p style="margin:8px 0 0 0;color:#5f6368;">No hay Audiencias Cortas para esta fecha.</p>
                        @endforelse
                      </td>
                    </tr>
                  </table>
                </td>
                <td class="td-gutter" width="32" style="width:32px;"></td>
              </tr>
            </table>
          </td>
        </tr>

        <!-- ===== JUECES AUSENTES ===== (con gutters + ESPACIADOR SUPERIOR) -->
        <tr>
          <td>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
              <tr>
                <td class="td-gutter" width="32" style="width:32px;"></td>
                <td>
                  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" class="w-inner" style="width:736px;max-width:736px;">
                    <!-- ESPACIADOR superior -->
                    <tr>
                      <td style="height:20px;line-height:20px;font-size:0;">&nbsp;</td>
                    </tr>
                    <tr>
                      <!--[if mso]>
                        <td style="padding:0 0 12px 0;"><h2 style="margin:0;font-size:20px;line-height:24px;">Jueces Ausentes</h2></td>
                      <![endif]-->
                      <!--[if !mso]><!-- -->
                        <td style="padding:0 0 12px 0;"><h2 style="margin:0;font-size:20px;line-height:24px;mso-line-height-rule:exactly;">👤 Jueces Ausentes</h2></td>
                      <!--<![endif]-->
                    </tr>
                    <tr>
                      <td style="padding:0 0 28px 0;">
                        @if(collect($juecesAusentes)->isEmpty())
                          <p style="margin:0;color:#5f6368;">Sin registros de ausencias para la fecha.</p>
                        @else
                          <table role="presentation" style="{{ $tbl }}">
                            <tr>
                              <th style="{{ $th }}">JUEZ/JUEZA</th>
                              <th style="{{ $th }}">FUNCIÓN</th>
                            </tr>
                            @foreach($juecesAusentes as $j)
                              <tr>
                                <td style="{{ $td }}">{{ $j['nombre'] }}</td>
                                <td style="{{ $td }}">{{ $j['funcion'] }}</td>
                              </tr>
                            @endforeach
                          </table>
                        @endif
                      </td>
                    </tr>
                  </table>
                </td>
                <td class="td-gutter" width="32" style="width:32px;"></td>
              </tr>
            </table>
          </td>
        </tr>

      </table>

      <!--[if mso]>
        </td></tr></table>
      <![endif]-->

    </td>
  </tr>
</table>
</body>
</html>
