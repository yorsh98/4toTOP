<?php

namespace App\Exports;

use App\Models\Audiencia;
use App\Models\Turno;
use App\Models\Ausentismo;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;


class ProgramacionDiariaExport implements WithEvents, WithTitle
{
    use Exportable;

    protected string $fecha;                 // YYYY-MM-DD
    protected ?array $juecesAusentes = null; // si vienes con arreglo manual, se usa; si no, se consulta DB

    // === Colores solicitados para "Acusados" ===
    private const ACUSADOS_HEAD_BG = '9CA3AF'; // gris más oscuro (Tailwind gray-400)
    private const ACUSADOS_ROW_BG  = 'D1D5DB'; // gris claro (Tailwind gray-300)
    private const ACUSADOS_SEP_RGB = 'FFFFFF'; // separador horizontal

    // Colores/estilos adicionales
    private const GREEN_HEADER = '00EFB5';     // cabecera bloque Jueces/Juezas
    private const RED_LABEL    = 'FF0000';     // "TURNO X:"
    private const MUTED_GRAY   = '9CA3AF';     // "NO HAY"

    public function __construct(string $fecha, ?array $juecesAusentes = null)
    {
        $this->fecha = $fecha;
        $this->juecesAusentes = $juecesAusentes; // si es null, se cargará desde DB
    }

    public function title(): string
    {
        return 'Prog. Diaria';
    }

    // ===== Helpers =====
    private function firstNonEmpty(array $row, array $candidates): ?string
    {
        foreach ($candidates as $key) {
            if (array_key_exists($key, $row)) {
                $val = is_string($row[$key] ?? null) ? trim($row[$key]) : null;
                if ($val !== null && $val !== '') return $val;
            }
        }
        return null;
    }

    private function getJuecesAusentesFromDB(string $fecha): array
    {
        // Se asume MySQL/MariaDB; LIKE cubrirá JUEZ/JUEZA
        $q = Ausentismo::query()
            ->select(['funcionario_nombre','cargo','observacion','tipo_permiso', 'fecha_inicio','fecha_termino'])
            ->where(function ($qq) {
                $qq->where('cargo', 'LIKE', '%JUEZ%')
                   ->orWhere('cargo', 'LIKE', '%JUEZA%');
            })
            ->whereDate('fecha_inicio', '<=', $fecha)
            ->where(function ($qq) use ($fecha) {
                $qq->whereNull('fecha_termino')
                   ->orWhereDate('fecha_termino', '>=', $fecha);
            })
            ->orderBy('funcionario_nombre');

        $rows = $q->get()->map(function ($r) {
            // Normaliza a mayúsculas
            $nombre  = mb_strtoupper(trim((string)($r->funcionario_nombre ?? '')), 'UTF-8');
            // Prioridad: observacion -> tipo_permiso
            $funcion = $this->firstNonEmpty($r->toArray(), ['observacion','tipo_permiso']);
            $funcion = $funcion ? mb_strtoupper($funcion, 'UTF-8') : '—';

            return [
                'nombre'  => $nombre !== '' ? $nombre : '—',
                'funcion' => $funcion,
            ];
        })->values()->all();

        return $rows;
    }

    private function dash($v): string
    {
        if ($v === null) return '—';
        if (is_string($v) && trim($v) === '') return '—';
        if (is_array($v)) return count($v) ? implode(', ', $v) : '—';
        return (string) $v;
    }

    private function normalizeTipo(?string $tipo): string
    {
        $t = mb_strtoupper(trim((string)$tipo));
        $t = str_replace(['Á','É','Í','Ó','Ú'], ['A','E','I','O','U'], $t);
        return $t;
    }

    /** Bordes para filas de ACUSADOS: verticales grises + separador horizontal (top/bottom) blanco/negro */
    private function applyAccusadosRowBorders(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'borders' => [
                'vertical' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                'left'     => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                'right'    => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                'top'      => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::ACUSADOS_SEP_RGB]],
                'bottom'   => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::ACUSADOS_SEP_RGB]],
            ],
        ]);
    }

    private function applyOuterMargin(Worksheet $sheet, int $firstRow, int $lastRow, string $firstCol = 'A', string $lastCol = 'H'): void
    {
        if ($lastRow < $firstRow) return;

        $sheet->getStyle("{$firstCol}{$firstRow}:{$lastCol}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => Border::BORDER_MEDIUM,
                        'color'      => ['rgb' => '000000'],
                    ],
                ],
            ]);
    }

    /** Relleno sin tocar bordes */
    private function fillRange(Worksheet $sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($range)->getFill()->getStartColor()->setRGB($rgb);
    }

    /** Filas (label/value) según tipo */
    private function rowsFor(string $tipo, $aud): array
    {
        $tipo = $this->normalizeTipo($tipo);

        $base = [
            'DURACION'  => ['label' => 'duracion',               'value' => $aud->duracion ?? null],
            'TESTIGOS'  => ['label' => 'Testigos',               'value' => $aud->num_testigos ?? null],
            'PERITOS'   => ['label' => 'Peritos',                'value' => $aud->num_peritos ?? null],
            'DELITO'    => ['label' => 'Delito',                 'value' => $aud->delito ?? null],
            'INHABS'    => ['label' => 'Jueces Inhabilitados',   'value' => collect($aud->jueces_inhabilitados ?? [])
                ->map(fn($item) => is_array($item) ? ($item['nombre_completo'] ?? null) : $item)
                ->filter()->values()->all(),
            ],
            'JUEZP'     => ['label' => 'Juez Presidente',        'value' => $aud->JuezP ?? null],
            'JUEZR'     => ['label' => 'Juez Redactor',          'value' => $aud->JuezR ?? null],
            'JUEZI'     => ['label' => 'Juez Integrante',        'value' => $aud->JuezI ?? null],
            'ENC_CAUSA' => ['label' => 'Encargado causa',        'value' => $aud->encargado_causa ?? null],
            'ACTA'      => ['label' => 'Acta de audiencia',      'value' => $aud->acta ?? null],
            'ENC_TT'    => ['label' => 'Encargado de TTyPP',     'value' => $aud->encargado_ttp ?? null],
            'ENC_TTZ'   => ['label' => 'Encargado de Zoom',      'value' => $aud->encargado_ttp_zoom ?? null],
            'CTA_ZOOM'  => ['label' => 'Cuenta Zoom',            'value' => $aud->cta_zoom ?? null],
            'ANFIT'     => ['label' => 'Anfitrión',              'value' => $aud->anfitrion ?? null],
        ];

        switch ($tipo) {
            case 'AUDIENCIA CORTA':
                return [$base['JUEZP'], $base['ENC_CAUSA'], $base['JUEZR'], $base['ACTA'], $base['JUEZI']];
            case 'LECTURA DE SENTENCIA':
            case 'LECTURA SENTENCIA':
            case 'LECTURA':
                return [$base['JUEZR'], $base['ENC_CAUSA'], $base['ACTA']];
            case 'JUICIO ORAL':
            case 'CONTINUACION JUICIO ORAL':
            case 'CONT. JUICIO ORAL':
            case 'CONTINUACIÓN JUICIO ORAL':
                return [
                    $base['DELITO'], $base['TESTIGOS'], $base['PERITOS'], $base['INHABS'], $base['ENC_CAUSA'],
                    $base['JUEZP'], $base['ACTA'], $base['JUEZR'], $base['ENC_TT'], $base['JUEZI'], $base['ENC_TTZ'],
                ];
            default:
                return [
                    $base['CTA_ZOOM'], $base['DELITO'], $base['INHABS'], $base['JUEZP'],
                    $base['JUEZR'], $base['JUEZI'], $base['ENC_CAUSA'], $base['ACTA'],
                ];
        }
    }

    private function accusedLayout(string $tipo): string
    {
        $tipo = $this->normalizeTipo($tipo);

        // Usar layout "double" (dos columnas con Nombre/Situación)
        // tanto para Juicio Oral como para Audiencias Cortas.
        return in_array($tipo, [
            'JUICIO ORAL',
            'CONTINUACION JUICIO ORAL',
            'CONT. JUICIO ORAL',
            'CONTINUACIÓN JUICIO ORAL',
            'AUDIENCIA CORTA', 
            'LECTURA DE SENTENCIA',
            'LECTURA SENTENCIA',
            'LECTURA',
        ], true) ? 'double' : 'single';
    }


    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // ===== Estilos base =====
                $borderThin = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]]];
                $titleStyle = [
                    'font' => ['bold' => true, 'size' => 18, 'name' => 'Aptos Display'],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];
                $labelCellStyle = [
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                ];
                $valueCellLeft = [
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                ];
                $mutedStyle = ['font' => ['italic' => true, 'color' => ['rgb' => self::MUTED_GRAY]]];

                foreach (['A'=>16,'B'=>16,'C'=>20,'D'=>18,'E'=>18,'F'=>18,'G'=>18,'H'=>22] as $col=>$w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                $sectionBigTitle = [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => self::RED_LABEL]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ];

                $cardOutlineOnly = ['borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']]]];
                $cardBodyFill    = ['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']]];
                $headerStyle = fn(string $rgb) => [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,   // ← antes CENTER
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                        'indent'     => 1,                            // sangría suave
                    ],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
                ];


                // ===== Encabezado del documento =====
                $row = 1;
                $sheet->setCellValue("A{$row}", 'Programación de Audiencias 4°TOP Santiago');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($titleStyle);
                $row++;

                $sheet->setCellValue("A{$row}", Carbon::parse($this->fecha)->format('d/m/Y'));
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray(['font' => ['bold' => true, 'size' => 18, 'name' => 'Aptos Display']]);
                $row++;

                // ===== BLOQUE: Turnos (bajo título y fecha) =====
                $row++; // espacio
                $turno2 = Turno::find(2);

                // tamaños más grandes para TURNOS
                $turnoFontSize = 15;     // tamaño letra
                $turnoRowHeight = 24;    // mayor altura de fila

                // Helper para pintar cada línea de turno (más grande)
                $paintTurno = function(string $etiqueta, array $nombres) use ($sheet, &$row, $turnoFontSize, $turnoRowHeight) {
                    // Label en A (rojo), contenido en B:H
                    $sheet->setCellValue("A{$row}", $etiqueta);
                    $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize($turnoFontSize)->getColor()->setRGB(self::RED_LABEL);
                    $sheet->getStyle("A{$row}")->getAlignment()
                          ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                          ->setVertical(Alignment::VERTICAL_CENTER);

                    $sheet->mergeCells("B{$row}:H{$row}");

                    $texto = 'NO HAY';
                    $isEmpty = true;
                    $clean = array_values(array_filter(array_map(fn($x) => trim((string)$x), $nombres), fn($x) => $x !== ''));
                    if (count($clean)) {
                        $texto = implode(' - ', $clean);
                        $isEmpty = false;
                    }

                    $sheet->setCellValue("B{$row}", $texto);

                    // bordes de la línea
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                        'borders' => [
                            'top'    => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            'bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            'left'   => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']],
                            'right'  => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']], // <- corregido (sin espacios extra)
                        ],
                    ]);

                    // tipografía y alineación más grande para el contenido
                    $sheet->getStyle("B{$row}:H{$row}")
                          ->getFont()->setSize($turnoFontSize);
                    $sheet->getStyle("B{$row}:H{$row}")
                          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)
                                          ->setVertical(Alignment::VERTICAL_CENTER)
                                          ->setWrapText(true)
                                          ->setIndent(1);

                    // color “NO HAY”
                    if ($isEmpty) {
                        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB(self::MUTED_GRAY);
                    } else {
                        $sheet->getStyle("B{$row}")->getFont()->getColor()->setRGB('000000');
                    }

                    // altura de fila más grande
                    $sheet->getRowDimension($row)->setRowHeight($turnoRowHeight);

                    $row++;
                };

                // Pinta los tres turnos (fuente +2 y altura aumentada)
                $paintTurno('TURNO 1:', [$turno2->TM1 ?? null]);
                $paintTurno('TURNO 2:', [$turno2->TM2 ?? null]);
                $paintTurno('TURNO 3:', [$turno2->TM3 ?? null]);

                $row += 1; // espacio antes de las audiencias

                // ===== ORDEN y recolección de audiencias =====
                // 1) Prioridad de sección
                $priority = [
                    'JUICIO ORAL'               => 1,
                    'CONTINUACION JUICIO ORAL'  => 1,
                    'CONT. JUICIO ORAL'         => 1,
                    'CONTINUACIÓN JUICIO ORAL'  => 1,

                    'LECTURA DE SENTENCIA'      => 2,
                    'LECTURA SENTENCIA'         => 2,
                    'LECTURA'                   => 2,

                    'AUDIENCIA CORTA'           => 3,
                ];

                // 2) Parse sala a número (p.ej. "Sala 801 (Zoom)" -> 801)
                $parseSala = function ($raw) {
                    if ($raw === null) return null;
                    if (preg_match('/\d{3,}/', (string)$raw, $m)) {
                        return (int) $m[0];
                    }
                    return null;
                };

                // 3) Recolección + orden por: sección → sala asc (num) → hora asc; sin sala al final
                $audiencias = Audiencia::query()
                    ->whereDate('fecha', $this->fecha)
                    ->get()
                    ->sort(function ($a, $b) use ($priority, $parseSala) {
                        $ta = strtoupper(trim((string)($a->tipo_audiencia ?? '')));
                        $tb = strtoupper(trim((string)($b->tipo_audiencia ?? '')));
                        $ra = $priority[$ta] ?? 99;
                        $rb = $priority[$tb] ?? 99;
                        if ($ra !== $rb) return $ra <=> $rb;

                        $sa = $parseSala($a->sala ?? null);
                        $sb = $parseSala($b->sala ?? null);
                        $ga = is_null($sa) ? 1 : 0;
                        $gb = is_null($sb) ? 1 : 0;
                        if ($ga !== $gb) return $ga <=> $gb;

                        if ($ga === 0 && $sa !== $sb) return $sa <=> $sb;

                        $ha = $a->hora_inicio instanceof \DateTimeInterface
                            ? $a->hora_inicio->format('H:i')
                            : (filled($a->hora_inicio) ? Carbon::parse($a->hora_inicio)->format('H:i') : '');
                        $hb = $b->hora_inicio instanceof \DateTimeInterface
                            ? $b->hora_inicio->format('H:i')
                            : (filled($b->hora_inicio) ? Carbon::parse($b->hora_inicio)->format('H:i') : '');

                        return strcmp($ha, $hb);
                    })
                    ->values();

                if ($audiencias->isEmpty()) {
                    $sheet->setCellValue("A{$row}", 'No se encontraron audiencias para la fecha seleccionada.');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                    $row += 2;

                    // ===== Bloque Jueces/Juezas al final aunque no haya audiencias (ANGOSTO C:G) =====
                    $row = $this->renderJuecesAusentes($sheet, $row, $borderThin, 'C', 'G');
                    $this->applyOuterMargin($sheet, 1, max(1, $row - 1));
                    return;
                }

                $printedSection = [
                    'AUDIENCIA CORTA'      => false,
                    'LECTURA DE SENTENCIA' => false,
                    'LECTURA SENTENCIA'    => false,
                    'LECTURA'              => false,
                ];
                $printedSubtitle = [
                    'AUDIENCIA CORTA'      => false,
                    'LECTURA DE SENTENCIA' => false,
                    'LECTURA SENTENCIA'    => false,
                    'LECTURA'              => false,
                ];

                foreach ($audiencias as $aud) {
                    $tipo = $this->normalizeTipo($aud->tipo_audiencia ?? '');

                    // Color cabecera por tipo
                    if (in_array($tipo, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)) {
                        $headerColor = '3B82F6';
                    } elseif ($tipo === 'AUDIENCIA CORTA') {
                        $headerColor = '10B981';
                    } elseif (in_array($tipo, ['LECTURA DE SENTENCIA','LECTURA SENTENCIA','LECTURA'], true)) {
                        $headerColor = 'A78BFA';
                    } else {
                        $headerColor = '00A3A3';
                    }

                    // Secciones únicas
                    if ($tipo === 'AUDIENCIA CORTA' && !$printedSection['AUDIENCIA CORTA']) {
                        $sheet->setCellValue("A{$row}", "↙  AUDIENCIAS CORTAS  ↘");
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionBigTitle);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                        $row++;

                        if (!$printedSubtitle['AUDIENCIA CORTA']) {
                            $sheet->setCellValue("A{$row}", 'Anfitrión: ' . ($aud->anfitrion ?? '—'));
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $row++;

                            $zoom = (string)($aud->cta_zoom ?? '');
                            $sheet->setCellValue("A{$row}", $zoom !== '' ? $zoom : '—');
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            if ($zoom !== '') {
                                $sheet->getCell("A{$row}")->getHyperlink()->setUrl($zoom);
                                $sheet->getStyle("A{$row}:H{$row}")->getFont()->getColor()->setRGB('0563C1');
                                $sheet->getStyle("A{$row}:H{$row}")->getFont()->setUnderline(true);
                            }
                            $row++;

                            $printedSubtitle['AUDIENCIA CORTA'] = true;
                        }
                        $printedSection['AUDIENCIA CORTA'] = true;
                    }

                    if (in_array($tipo, ['LECTURA DE SENTENCIA','LECTURA SENTENCIA','LECTURA'], true)
                        && !$printedSection['LECTURA DE SENTENCIA']) {
                        $sheet->setCellValue("A{$row}", "↙  LECTURA DE SENTENCIA  ↘");
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionBigTitle);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                        $row++;

                        if (!$printedSubtitle['LECTURA DE SENTENCIA']) {
                            $zoom = (string)($aud->cta_zoom ?? '');
                            $sheet->setCellValue("A{$row}", $zoom !== '' ? $zoom : '—');
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            if ($zoom !== '') {
                                $sheet->getCell("A{$row}")->getHyperlink()->setUrl($zoom);
                                $sheet->getStyle("A{$row}:H{$row}")->getFont()->getColor()->setRGB('0563C1');
                                $sheet->getStyle("A{$row}:H{$row}")->getFont()->setUnderline(true);
                            }
                            $row++;

                            $printedSubtitle['LECTURA DE SENTENCIA'] = true;
                            $printedSubtitle['LECTURA SENTENCIA']    = true;
                            $printedSubtitle['LECTURA']              = true;
                        }

                        $printedSection['LECTURA DE SENTENCIA'] = true;
                        $printedSection['LECTURA SENTENCIA']    = true;
                        $printedSection['LECTURA']              = true;
                    }

                    // ===== Cabecera de cada audiencia =====
                    $partes = [
                        sprintf('Sala %s y %s', (string)($aud->sala ?? '—'), (string)($aud->cta_zoom ?? '—')),
                        sprintf('%s Horas', optional($aud->hora_inicio instanceof \DateTimeInterface ? $aud->hora_inicio : Carbon::parse($aud->hora_inicio))->format('H:i')),
                        (string)($aud->tipo_audiencia ?? '—'),
                        'RIT ' . $this->dash($aud->rit ?? null),
                        'RUC ' . $this->dash($aud->ruc ?? null),
                    ];
                    if (in_array($tipo, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)) {
                        $dur = $this->dash($aud->duracion ?? null);
                        if ($dur !== '—') $partes[] = 'Duración: ' . $dur;
                    }
                    $obs = isset($aud->obs) ? trim((string)$aud->obs) : '';
                    if ($obs !== '') $partes[] = ' ' . $obs;

                    $cabecera = implode(' - ', $partes);

                    $sheet->setCellValue("A{$row}", $cabecera);
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($headerStyle($headerColor));
                    $sheet->getRowDimension($row)->setRowHeight(20);
                    $headerRow = $row;
                    $row++;

                    // ===== Ficha 2x2 con DELITO a lo ancho =====
                    $rows  = $this->rowsFor($tipo, $aud);
                    $start = $row;
                    $carry = null;

                    foreach ($rows as $r) {
                        $label = $r['label'] ?? '';
                        $value = $r['value'] ?? null;
                        $isDelito = strtoupper($label) === 'DELITO';

                        if ($isDelito) {
                            if ($carry) {
                                $sheet->setCellValue("A{$row}", $carry['label']); $sheet->mergeCells("A{$row}:B{$row}");
                                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                                $sheet->setCellValue("C{$row}", $this->dash($carry['value'])); $sheet->mergeCells("C{$row}:D{$row}");
                                $sheet->getStyle("C{$row}:D{$row}")->applyFromArray($valueCellLeft);

                                $sheet->mergeCells("E{$row}:H{$row}");
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                                $row++;
                                $carry = null;
                            }

                            $sheet->setCellValue("A{$row}", $label); $sheet->mergeCells("A{$row}:B{$row}");
                            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                            $sheet->setCellValue("C{$row}", $this->dash($value)); $sheet->mergeCells("C{$row}:H{$row}");
                            $sheet->getStyle("C{$row}:H{$row}")->applyFromArray($valueCellLeft);

                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $row++;
                            continue;
                        }

                        if ($carry === null) {
                            $carry = $r;
                        } else {
                            // par en una fila
                            $sheet->setCellValue("A{$row}", $carry['label']); $sheet->mergeCells("A{$row}:B{$row}");
                            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                            $sheet->setCellValue("C{$row}", $this->dash($carry['value'])); $sheet->mergeCells("C{$row}:D{$row}");
                            $sheet->getStyle("C{$row}:D{$row}")->applyFromArray($valueCellLeft);

                            $sheet->setCellValue("E{$row}", $label); $sheet->mergeCells("E{$row}:F{$row}");
                            $sheet->getStyle("E{$row}:F{$row}")->applyFromArray($labelCellStyle);

                            $sheet->setCellValue("G{$row}", $this->dash($value)); $sheet->mergeCells("G{$row}:H{$row}");
                            $sheet->getStyle("G{$row}:H{$row}")->applyFromArray($valueCellLeft);

                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $row++;
                            $carry = null;
                        }
                    }

                    if ($carry) {
                        $sheet->setCellValue("A{$row}", $carry['label']); $sheet->mergeCells("A{$row}:B{$row}");
                        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                        $sheet->setCellValue("C{$row}", $this->dash($carry['value'])); $sheet->mergeCells("C{$row}:D{$row}");
                        $sheet->getStyle("C{$row}:D{$row}")->applyFromArray($valueCellLeft);

                        $sheet->mergeCells("E{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $row++;
                    }

                    // Alineación general de la ficha
                    $sheet->getStyle("A{$start}:H".($row-1))->getAlignment()->setWrapText(true);
                    $sheet->getStyle("A{$start}:H".($row-1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                    // ===== ACUSADOS =====
                    $acusados = array_values((array)($aud->acusados ?? []));
                    $layout = $this->accusedLayout($tipo);

                    $acusadosStart = $row;
                    $acusadosEnd   = $row - 1;

                    if (empty($acusados)) {
                        $sheet->setCellValue("A{$row}", '— Sin acusados —');
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                        $acusadosEnd = $row;
                        $row++;

                    } elseif (count($acusados) <= 4) {
                        // Encabezado
                        $sheet->setCellValue("A{$row}", 'Acusado'); $sheet->mergeCells("A{$row}:C{$row}");
                        $sheet->setCellValue("D{$row}", 'Situación'); $sheet->mergeCells("D{$row}:E{$row}");
                        $sheet->setCellValue("F{$row}", 'Medidas cautelares'); $sheet->mergeCells("F{$row}:G{$row}");
                        $sheet->setCellValue("H{$row}", 'Forma notificación');
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                        $this->fillRange($sheet, "A{$row}:H{$row}", self::ACUSADOS_HEAD_BG);
                        $row++;

                        foreach ($acusados as $ac) {
                            $sheet->setCellValue("A{$row}", $this->dash($ac['nombre_completo'] ?? null)); $sheet->mergeCells("A{$row}:C{$row}");
                            $sheet->setCellValue("D{$row}", $this->dash($ac['situacion'] ?? null));       $sheet->mergeCells("D{$row}:E{$row}");
                            $sheet->setCellValue("F{$row}", $this->dash($ac['medida_cautelar'] ?? null));  $sheet->mergeCells("F{$row}:G{$row}");
                            $sheet->setCellValue("H{$row}", $this->dash($ac['forma_notificacion'] ?? null));

                            $this->applyAccusadosRowBorders($sheet, $row);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                            $this->fillRange($sheet, "A{$row}:H{$row}", self::ACUSADOS_ROW_BG);

                            $acusadosEnd = $row;
                            $row++;
                        }

                    } else {
                        if ($layout === 'single') {
                            $sheet->setCellValue("A{$row}", 'Acusados'); $sheet->mergeCells("A{$row}:E{$row}");
                            $sheet->setCellValue("F{$row}", 'Situación de Libertad'); $sheet->mergeCells("F{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                            $this->fillRange($sheet, "A{$row}:H{$row}", self::ACUSADOS_HEAD_BG);
                            $row++;

                            foreach ($acusados as $ac) {
                                $sheet->setCellValue("A{$row}", $this->dash($ac['nombre_completo'] ?? null)); $sheet->mergeCells("A{$row}:E{$row}");
                                $sheet->setCellValue("F{$row}", $this->dash($ac['situacion'] ?? null));       $sheet->mergeCells("F{$row}:H{$row}");
                                $this->applyAccusadosRowBorders($sheet, $row);
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                                $this->fillRange($sheet, "A{$row}:H{$row}", self::ACUSADOS_ROW_BG);
                                $acusadosEnd = $row;
                                $row++;
                            }
                        } else {
                            $sheet->setCellValue("A{$row}", 'Acusados'); $sheet->mergeCells("A{$row}:C{$row}");
                            $sheet->setCellValue("D{$row}", 'Situación');
                            $sheet->setCellValue("E{$row}", 'Acusados'); $sheet->mergeCells("E{$row}:G{$row}");
                            $sheet->setCellValue("H{$row}", 'Situación');
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                            $this->fillRange($sheet, "A{$row}:H{$row}", self::ACUSADOS_HEAD_BG);
                            $row++;

                            $n   = count($acusados);
                            $mid = (int) ceil($n / 2);
                            $left  = array_slice($acusados, 0, $mid);
                            $right = array_slice($acusados, $mid);
                            $maxRows = max(count($left), count($right));

                            for ($i = 0; $i < $maxRows; $i++) {
                                if (isset($left[$i])) {
                                    $sheet->setCellValue("A{$row}", $this->dash($left[$i]['nombre_completo'] ?? null)); $sheet->mergeCells("A{$row}:C{$row}");
                                    $sheet->setCellValue("D{$row}", $this->dash($left[$i]['situacion'] ?? null));
                                } else {
                                    $sheet->mergeCells("A{$row}:C{$row}");
                                }

                                if (isset($right[$i])) {
                                    $sheet->setCellValue("E{$row}", $this->dash($right[$i]['nombre_completo'] ?? null)); $sheet->mergeCells("E{$row}:G{$row}");
                                    $sheet->setCellValue("H{$row}", $this->dash($right[$i]['situacion'] ?? null));
                                } else {
                                    $sheet->mergeCells("E{$row}:G{$row}");
                                }

                                $this->applyAccusadosRowBorders($sheet, $row);
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                                $this->fillRange($sheet, "A{$row}:H{$row}", self::ACUSADOS_ROW_BG);

                                $acusadosEnd = $row;
                                $row++;
                            }
                        }
                    }

                    // ===== Card: borde + fondo (sin pisar Acusados) =====
                    $cardTop    = $headerRow;
                    $cardBottom = $row - 1;

                    $sheet->getStyle("A{$cardTop}:H{$cardBottom}")->applyFromArray($cardOutlineOnly);

                    $bodyStart = $cardTop + 1;
                    if ($bodyStart <= $cardBottom) {
                        if (isset($acusadosStart, $acusadosEnd) && $acusadosStart <= $acusadosEnd) {
                            if ($bodyStart <= $acusadosStart - 1) {
                                $sheet->getStyle("A{$bodyStart}:H".($acusadosStart - 1))->applyFromArray($cardBodyFill);
                            }
                            if ($acusadosEnd + 1 <= $cardBottom) {
                                $sheet->getStyle("A".($acusadosEnd + 1).":H{$cardBottom}")->applyFromArray($cardBodyFill);
                            }
                        } else {
                            $sheet->getStyle("A{$bodyStart}:H{$cardBottom}")->applyFromArray($cardBodyFill);
                        }

                        $sheet->getStyle("C{$bodyStart}:H{$cardBottom}")->getAlignment()->setIndent(1);
                        $sheet->getStyle("C{$bodyStart}:H{$cardBottom}")->applyFromArray($valueCellLeft);
                    }

                    $row += 1; // margen entre audiencias
                }

                // ===== BLOQUE FINAL: JUEZ/JUEZA — FUNCIÓN (ANGOSTO C:G) =====
                $row = $this->renderJuecesAusentes($sheet, $row, $borderThin, 'C', 'G');

                // ===== Marco negro alrededor de TODO =====
                $this->applyOuterMargin($sheet, 1, max(1, $row - 1));
            },
        ];
    }

    /**
     * Renderiza la tabla final "JUEZ/JUEZA — FUNCIÓN" en un bloque angosto.
     * Devuelve el siguiente $row disponible.
     */
    private function renderJuecesAusentes(
        Worksheet $sheet,
        int $row,
        array $borderThin,
        string $firstCol = 'C',
        string $lastCol  = 'G'
    ): int {
        // Seguridad básica
        if (ord($firstCol) > ord($lastCol)) {
            [$firstCol, $lastCol] = [$lastCol, $firstCol];
        }

        // Columnas del bloque compacto
        $cols = range($firstCol, $lastCol);
        $colCount = count($cols);

        // Asegura al menos 3 columnas
        if ($colCount < 3) {
            $lastCol = chr(ord($firstCol) + 2);
            $cols = range($firstCol, $lastCol);
            $colCount = 3;
        }

        // Partición: nombre ocupa (colCount - 2) columnas, función ocupa 2
        $nameSpan = array_slice($cols, 0, max(1, $colCount - 2));
        $funcSpan = array_slice($cols, max(1, $colCount - 2));

        // Helpers para rangos
        $range = function(array $span, int $r) {
            return $span[0] . $r . ':' . end($span) . $r;
        };
        $blockRange = function(int $r1, int $r2) use ($firstCol, $lastCol) {
            return $firstCol . $r1 . ':' . $lastCol . $r2;
        };

        $row += 1; // separador visual
        $headerRow = $row;

        // ===== Cabecera verde compacta =====
        $sheet->setCellValue($nameSpan[0].$row, 'JUEZ / JUEZA');
        $sheet->mergeCells($range($nameSpan, $row));

        $sheet->setCellValue($funcSpan[0].$row, 'FUNCIÓN');
        $sheet->mergeCells($range($funcSpan, $row));

        $sheet->getStyle($blockRange($row, $row))->applyFromArray($borderThin);
        $sheet->getStyle($blockRange($row, $row))->getFont()->setBold(true);
        $sheet->getStyle($blockRange($row, $row))
              ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Fondo cabecera
        $this->fillRange($sheet, $blockRange($row, $row), self::GREEN_HEADER);
        $row++;

        // ===== Datos =====
        $data = $this->juecesAusentes ?? $this->getJuecesAusentesFromDB($this->fecha);

        if (empty($data)) {
            // Fila vacía compacta
            $sheet->setCellValue($nameSpan[0].$row, '—');
            $sheet->mergeCells($range($nameSpan, $row));

            $sheet->setCellValue($funcSpan[0].$row, '—');
            $sheet->mergeCells($range($funcSpan, $row));

            $sheet->getStyle($blockRange($row, $row))->applyFromArray($borderThin);
            $row++;
        } else {
            foreach ($data as $j) {
                $nombre  = $this->dash($j['nombre']  ?? null);
                $funcion = $this->dash($j['funcion'] ?? null);

                // Nombre (span ancho)
                $sheet->setCellValue($nameSpan[0].$row, $nombre);
                $sheet->mergeCells($range($nameSpan, $row));

                // Función (2 columnas)
                $sheet->setCellValue($funcSpan[0].$row, $funcion);
                $sheet->mergeCells($range($funcSpan, $row));

                $sheet->getStyle($blockRange($row, $row))->applyFromArray($borderThin);

                // Alineaciones amigables
                $sheet->getStyle($blockRange($row, $row))
                      ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle($blockRange($row, $row))
                      ->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle($blockRange($row, $row))
                      ->getAlignment()->setWrapText(true);

                $row++;
            }
        }

        $lastDataRow = $row - 1;

        // Outline alrededor del bloque compacto
        if ($lastDataRow >= $headerRow) {
            $sheet->getStyle($blockRange($headerRow, $lastDataRow))
                  ->applyFromArray([
                      'borders' => [
                          'outline' => [
                              'borderStyle' => Border::BORDER_MEDIUM,
                              'color'       => ['rgb' => '000000'],
                          ],
                      ],
                  ]);
        }

        return $row;
    }
}
