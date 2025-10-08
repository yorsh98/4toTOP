<?php

namespace App\Exports;

use App\Models\Audiencia;
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

    protected string $fecha; // YYYY-MM-DD

    public function __construct(string $fecha)
    {
        $this->fecha = $fecha;
    }

    public function title(): string
    {
        return 'Prog. Diaria';
    }

    // ===== Helpers =====
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

    /** Siempre alinearemos valores a la izquierda, ya no centramos nada especial */
    private function shouldCenter(string $label): bool
    {
        return false;
    }

    private function applyOuterMargin(Worksheet $sheet, int $firstRow, int $lastRow, string $firstCol = 'A', string $lastCol = 'H'): void
    {
        if ($lastRow < $firstRow) return;

        $sheet->getStyle("{$firstCol}{$firstRow}:{$lastCol}{$lastRow}")
            ->applyFromArray([
                'borders' => [
                    'outline' => [
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM,
                        'color'      => ['rgb' => '000000'], // negro
                    ],
                ],
            ]);
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
                ->map(function ($item) {
                    return is_array($item) ? ($item['nombre_completo'] ?? null) : $item;
                })
                ->filter()
                ->values()
                ->all(),
            ],
            'JUEZP'     => ['label' => 'Juez Presidente',        'value' => $aud->JuezP ?? null],
            'JUEZR'     => ['label' => 'Juez Redactor',          'value' => $aud->JuezR ?? null],
            'JUEZI'     => ['label' => 'Juez Integrante',        'value' => $aud->JuezI ?? null],
            'ENC_CAUSA' => ['label' => 'Encargado causa',        'value' => $aud->encargado_causa ?? null],
            'ACTA'      => ['label' => 'Acta de audiencia',      'value' => $aud->acta ?? null],
            'ENC_TT'    => ['label' => 'Encargado de TTyPP',     'value' => $aud->encargado_ttp ?? null],
            'ENC_TTZ'   => ['label' => 'Encargado de Zoom',  'value' => $aud->encargado_ttp_zoom ?? null],
            'CTA_ZOOM'  => ['label' => 'Cuenta Zoom',            'value' => $aud->cta_zoom ?? null],
            'ANFIT'     => ['label' => 'Anfitrión',              'value' => $aud->anfitrion ?? null],
        ];

        switch ($tipo) {
            case 'AUDIENCIA CORTA':
                return [
                    $base['JUEZP'],
                    $base['ENC_CAUSA'],
                    $base['JUEZR'],
                    $base['ACTA'],
                    $base['JUEZI'],
                ];

            case 'LECTURA DE SENTENCIA':
            case 'LECTURA SENTENCIA':
            case 'LECTURA':
                return [
                    $base['JUEZR'],
                    $base['ENC_CAUSA'],
                    $base['ACTA'],
                ];

            case 'JUICIO ORAL':
            case 'CONTINUACION JUICIO ORAL':
            case 'CONT. JUICIO ORAL':
            case 'CONTINUACIÓN JUICIO ORAL':
                return [
                    $base['DELITO'],
                    $base['TESTIGOS'],
                    $base['PERITOS'],
                    $base['INHABS'],
                    $base['ENC_CAUSA'],
                    $base['JUEZP'],
                    $base['ACTA'],
                    $base['JUEZR'],
                    $base['ENC_TT'],
                    $base['JUEZI'],
                    $base['ENC_TTZ'],                    
                ];

            default:
                return [
                    $base['CTA_ZOOM'],
                    $base['DELITO'],
                    $base['INHABS'],
                    $base['JUEZP'],
                    $base['JUEZR'],
                    $base['JUEZI'],
                    $base['ENC_CAUSA'],
                    $base['ACTA'],
                ];
        }
    }

    private function accusedLayout(string $tipo): string
    {
        $tipo = $this->normalizeTipo($tipo);
        return in_array($tipo, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)
            ? 'double'
            : 'single';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                /** @var Worksheet $sheet */
                $sheet = $event->sheet->getDelegate();

                // ===== Estilos base =====
                $borderThin = [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ];
                $titleStyle = [
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];
                $labelCellStyle = [
                    'font' => ['bold' => true],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,  // <- antes RIGHT
                        'vertical'   => Alignment::VERTICAL_TOP,
                        'wrapText'   => true,
                    ],
                ];
                $valueCellLeft = [
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_LEFT,
                        'vertical'   => Alignment::VERTICAL_TOP,
                        'wrapText'   => true,
                    ],
                ];
                $mutedStyle = ['font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF']]];

                foreach (['A'=>16,'B'=>16,'C'=>20,'D'=>18,'E'=>18,'F'=>18,'G'=>18,'H'=>22] as $col=>$w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // Títulos de sección (rojo con borde fino)
                $sectionBigTitle = [
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FF0000']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ];

                // Borde de “card” (solo contorno)
                $cardOutlineOnly = [
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'D1D5DB']],
                    ],
                ];

                // Fondo del cuerpo de la card (sin afectar cabecera)
                $cardBodyFill = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'F9FAFB'],
                    ],
                ];

                // Cabecera coloreada por tipo
                $headerStyle = function (string $rgb) {
                    return [
                        'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $rgb]],
                    ];
                };

                // ===== Encabezado del documento =====
                $row = 1;
                $sheet->setCellValue("A{$row}", 'Programación de Audiencias 4°TOP Santiago');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($titleStyle);
                $row++;

                $sheet->setCellValue("A{$row}", Carbon::parse($this->fecha)->format('d-m-Y'));
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}:H{$row}")
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $row += 2;

                // Orden visible: Juicio Oral, Cont. Juicio Oral, Audiencia Corta, Lectura
                $priority = [
                    'JUICIO ORAL'               => 1,
                    'CONTINUACION JUICIO ORAL'  => 2,
                    'CONT. JUICIO ORAL'         => 2,
                    'CONTINUACIÓN JUICIO ORAL'  => 2,                    
                    'LECTURA DE SENTENCIA'      => 3,
                    'LECTURA SENTENCIA'         => 3,
                    'LECTURA'                   => 3,
                    'AUDIENCIA CORTA'           => 4,
                ];

                $audiencias = Audiencia::query()
                    ->whereDate('fecha', $this->fecha)
                    ->get()
                    ->sortBy(function ($a) use ($priority) {
                        $tipoNorm = $this->normalizeTipo($a->tipo_audiencia ?? '');
                        $rank     = $priority[$tipoNorm] ?? 99;
                        $sala = (string) ($a->sala ?? '');
                        $hora = optional(
                            $a->hora_inicio instanceof \DateTimeInterface ? $a->hora_inicio : \Carbon\Carbon::parse($a->hora_inicio)
                        )->format('H:i');
                        return [$rank, $sala, $hora];
                    })
                    ->values();

                if ($audiencias->isEmpty()) {
                    $sheet->setCellValue("A{$row}", 'No se encontraron audiencias para la fecha seleccionada.');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                    // A la izquierda también aquí
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);

                    // Marco negro alrededor de todo lo generado (desde el título hasta este mensaje)
                    $lastRowIfEmpty = $row;
                    $this->applyOuterMargin($sheet, 1, $lastRowIfEmpty);

                    return;
                }

                // Títulos de sección: imprimir una vez
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

                    // Color de cabecera por tipo
                    if (in_array($tipo, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)) {
                        $headerColor = '3B82F6'; // azul
                    } elseif ($tipo === 'AUDIENCIA CORTA') {
                        $headerColor = '10B981'; // verde
                    } elseif (in_array($tipo, ['LECTURA DE SENTENCIA','LECTURA SENTENCIA','LECTURA'], true)) {
                        $headerColor = 'A78BFA'; // lila
                    } else {
                        $headerColor = '00A3A3'; // fallback (teal)
                    }

                    // ===== Títulos de sección (una vez) =====
                    if ($tipo === 'AUDIENCIA CORTA' && !$printedSection['AUDIENCIA CORTA']) {
                        $sheet->setCellValue("A{$row}", "↙  AUDIENCIAS CORTAS  ↘");
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionBigTitle);
                        $sheet->getRowDimension($row)->setRowHeight(22);
                        $row++;

                        // Subtítulos centrados (se mantienen)
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
                   // Construcción segura de la cabecera sin guiones colgando
                    $partes = [
                        sprintf('Sala %s y %s',
                            (string)($aud->sala ?? '—'),
                            (string)($aud->cta_zoom ?? '—')
                        ),
                        sprintf('%s Horas',
                            optional($aud->hora_inicio instanceof \DateTimeInterface ? $aud->hora_inicio : Carbon::parse($aud->hora_inicio))->format('H:i')
                        ),
                        (string)($aud->tipo_audiencia ?? '—'),
                        'RIT ' . $this->dash($aud->rit ?? null),
                        'RUC ' . $this->dash($aud->ruc ?? null),
                    ];

                    // Agrega "Duración" solo para Juicios/Continuaciones y si trae algo
                    if (in_array($tipo, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)) {
                        $dur = $this->dash($aud->duracion ?? null);
                        if ($dur !== '—') {
                            $partes[] = 'Duración: ' . $dur;
                        }
                    }

                    // Agrega "Obs" solo si viene con texto no vacío
                    $obs = isset($aud->obs) ? trim((string)$aud->obs) : '';
                    if ($obs !== '') {
                        $partes[] = ' ' . $obs;
                    }

                    // Une todo con " - " (sin guión final)
                    $cabecera = implode(' - ', $partes);

                    $sheet->setCellValue("A{$row}", $cabecera);
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($headerStyle($headerColor));
                    $sheet->getRowDimension($row)->setRowHeight(20);
                    $headerRow = $row;
                    $row++;

                    // ===== Ficha 2x2, con DELITO a lo ancho =====
                    $rows  = $this->rowsFor($tipo, $aud);
                    $start = $row;
                    $carry = null;

                    foreach ($rows as $r) {
                        $label = $r['label'] ?? '';
                        $value = $r['value'] ?? null;
                        $isDelito = strtoupper($label) === 'DELITO';

                        if ($isDelito) {
                            if ($carry) {
                                // IZQ pendiente
                                $sheet->setCellValue("A{$row}", $carry['label']);
                                $sheet->mergeCells("A{$row}:B{$row}");
                                $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                                $sheet->setCellValue("C{$row}", $this->dash($carry['value']));
                                $sheet->mergeCells("C{$row}:D{$row}");
                                $sheet->getStyle("C{$row}:D{$row}")->applyFromArray($valueCellLeft);

                                // derecha vacía
                                $sheet->mergeCells("E{$row}:H{$row}");
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                                $row++;
                                $carry = null;
                            }

                            // DELITO en toda la fila (valor a la izquierda)
                            $sheet->setCellValue("A{$row}", $label);
                            $sheet->mergeCells("A{$row}:B{$row}");
                            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                            $sheet->setCellValue("C{$row}", $this->dash($value));
                            $sheet->mergeCells("C{$row}:H{$row}");
                            $sheet->getStyle("C{$row}:H{$row}")->applyFromArray($valueCellLeft);

                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $row++;
                            continue;
                        }

                        if ($carry === null) {
                            $carry = $r;
                        } else {
                            // par en una fila
                            // IZQ
                            $sheet->setCellValue("A{$row}", $carry['label']);
                            $sheet->mergeCells("A{$row}:B{$row}");
                            $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                            $sheet->setCellValue("C{$row}", $this->dash($carry['value']));
                            $sheet->mergeCells("C{$row}:D{$row}");
                            $sheet->getStyle("C{$row}:D{$row}")->applyFromArray($valueCellLeft);

                            // DER
                            $sheet->setCellValue("E{$row}", $label);
                            $sheet->mergeCells("E{$row}:F{$row}");
                            $sheet->getStyle("E{$row}:F{$row}")->applyFromArray($labelCellStyle);

                            $sheet->setCellValue("G{$row}", $this->dash($value));
                            $sheet->mergeCells("G{$row}:H{$row}");
                            $sheet->getStyle("G{$row}:H{$row}")->applyFromArray($valueCellLeft);

                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $row++;
                            $carry = null;
                        }
                    }

                    // si quedó uno sin par
                    if ($carry) {
                        $sheet->setCellValue("A{$row}", $carry['label']);
                        $sheet->mergeCells("A{$row}:B{$row}");
                        $sheet->getStyle("A{$row}:B{$row}")->applyFromArray($labelCellStyle);

                        $sheet->setCellValue("C{$row}", $this->dash($carry['value']));
                        $sheet->mergeCells("C{$row}:D{$row}");
                        $sheet->getStyle("C{$row}:D{$row}")->applyFromArray($valueCellLeft);

                        // derecha vacía
                        $sheet->mergeCells("E{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $row++;
                    }

                    // wrap + vertical top en toda la ficha
                    $sheet->getStyle("A{$start}:H".($row-1))->getAlignment()->setWrapText(true);
                    $sheet->getStyle("A{$start}:H".($row-1))->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                    // ===== ACUSADOS =====
                    $acusados = array_values((array)($aud->acusados ?? []));
                    $layout = $this->accusedLayout($tipo);

                    if (empty($acusados)) {
                        $sheet->setCellValue("A{$row}", '— Sin acusados —');
                        $sheet->mergeCells("A{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                        $row++;
                    } elseif (count($acusados) <= 4) {
                        // Detallado (una sección)
                        $sheet->setCellValue("A{$row}", 'Acusado');
                        $sheet->mergeCells("A{$row}:C{$row}");
                        $sheet->setCellValue("D{$row}", 'Situación');
                        $sheet->mergeCells("D{$row}:E{$row}");
                        $sheet->setCellValue("F{$row}", 'Medidas cautelares');
                        $sheet->mergeCells("F{$row}:G{$row}");
                        $sheet->setCellValue("H{$row}", 'Forma notificación');
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                        // Encabezados también a la izquierda
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                        $row++;

                        foreach ($acusados as $ac) {
                            $sheet->setCellValue("A{$row}", $this->dash($ac['nombre_completo'] ?? null));
                            $sheet->mergeCells("A{$row}:C{$row}");

                            $sheet->setCellValue("D{$row}", $this->dash($ac['situacion'] ?? null));
                            $sheet->mergeCells("D{$row}:E{$row}");

                            $sheet->setCellValue("F{$row}", $this->dash($ac['medida_cautelar'] ?? null));
                            $sheet->mergeCells("F{$row}:G{$row}");

                            $sheet->setCellValue("H{$row}", $this->dash($ac['forma_notificacion'] ?? null));
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                            $row++;
                        }
                    } else {
                        // Compacto
                        if ($layout === 'single') {
                            $sheet->setCellValue("A{$row}", 'Acusados');
                            $sheet->mergeCells("A{$row}:E{$row}");
                            $sheet->setCellValue("F{$row}", 'Situación de Libertad');
                            $sheet->mergeCells("F{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                            $row++;

                            foreach ($acusados as $ac) {
                                $sheet->setCellValue("A{$row}", $this->dash($ac['nombre_completo'] ?? null));
                                $sheet->mergeCells("A{$row}:E{$row}");
                                $sheet->setCellValue("F{$row}", $this->dash($ac['situacion'] ?? null));
                                $sheet->mergeCells("F{$row}:H{$row}");
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                                $row++;
                            }
                        } else {
                            $sheet->setCellValue("A{$row}", 'Acusados');
                            $sheet->mergeCells("A{$row}:C{$row}");
                            $sheet->setCellValue("D{$row}", 'Situación');
                            $sheet->setCellValue("E{$row}", 'Acusados');
                            $sheet->mergeCells("E{$row}:G{$row}");
                            $sheet->setCellValue("H{$row}", 'Situación');
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                            $row++;

                            $n   = count($acusados);
                            $mid = (int) ceil($n / 2);
                            $left  = array_slice($acusados, 0, $mid);
                            $right = array_slice($acusados, $mid);
                            $maxRows = max(count($left), count($right));

                            for ($i = 0; $i < $maxRows; $i++) {
                                if (isset($left[$i])) {
                                    $sheet->setCellValue("A{$row}", $this->dash($left[$i]['nombre_completo'] ?? null));
                                    $sheet->mergeCells("A{$row}:C{$row}");
                                    $sheet->setCellValue("D{$row}", $this->dash($left[$i]['situacion'] ?? null));
                                } else {
                                    $sheet->mergeCells("A{$row}:C{$row}");
                                }

                                if (isset($right[$i])) {
                                    $sheet->setCellValue("E{$row}", $this->dash($right[$i]['nombre_completo'] ?? null));
                                    $sheet->mergeCells("E{$row}:G{$row}");
                                    $sheet->setCellValue("H{$row}", $this->dash($right[$i]['situacion'] ?? null));
                                } else {
                                    $sheet->mergeCells("E{$row}:G{$row}");
                                }

                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($valueCellLeft);
                                $row++;
                            }
                        }
                    }

                    // ===== Card: borde + fondo de cuerpo (sin pisar cabecera) =====
                    $cardTop    = $headerRow;
                    $cardBottom = $row - 1;

                    $sheet->getStyle("A{$cardTop}:H{$cardBottom}")->applyFromArray($cardOutlineOnly);

                    $bodyStart = $cardTop + 1;
                    if ($bodyStart <= $cardBottom) {
                        $sheet->getStyle("A{$bodyStart}:H{$cardBottom}")->applyFromArray($cardBodyFill);
                        // Indent + asegurar izquierda para todo el cuerpo de la card
                        $sheet->getStyle("C{$bodyStart}:H{$cardBottom}")->getAlignment()->setIndent(1);
                        $sheet->getStyle("C{$bodyStart}:H{$cardBottom}")->applyFromArray($valueCellLeft);
                    }

                    // margen externo entre audiencias
                    $row += 1;
                }

                // ===== Marco negro alrededor de TODO el contenido generado =====
                $lastRowUsed = max(1, $row - 1);
                $this->applyOuterMargin($sheet, 1, $lastRowUsed);

            },
        ];
    }
}
