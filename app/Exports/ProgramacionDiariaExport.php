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

    /** Filas (label/value) según tipo */
    private function rowsFor(string $tipo, $aud): array
    {
        $tipo = $this->normalizeTipo($tipo);

        $base = [
            'DURACION'  => ['label' => 'DURACIÓN',               'value' => $aud->duracion ?? null],
            'TESTIGOS'  => ['label' => 'TESTIGOS',               'value' => $aud->num_testigos ?? null],
            'PERITOS'   => ['label' => 'PERITOS',                'value' => $aud->num_peritos ?? null],
            'DELITO'    => ['label' => 'DELITO',                 'value' => $aud->delito ?? null],
            'INHABS'    => ['label' => 'JUECES INHABILITADOS',   'value' => (array)($aud->jueces_inhabilitados ?? [])],
            'JUEZP'     => ['label' => 'JUEZ PRESIDENTE',        'value' => $aud->JuezP ?? null],
            'JUEZR'     => ['label' => 'JUEZ REDACTOR',          'value' => $aud->JuezR ?? null],
            'JUEZI'     => ['label' => 'JUEZ INTEGRANTE',        'value' => $aud->JuezI ?? null],
            'ENC_CAUSA' => ['label' => 'ENCARGADO CAUSA',        'value' => $aud->encargado_causa ?? null],
            'ACTA'      => ['label' => 'ACTA DE SALA',           'value' => $aud->acta ?? null],
            'ENC_TT'    => ['label' => 'ENCARGADOS DE TTPP',     'value' => $aud->encargado_ttp ?? null],
            'ENC_TTZ'   => ['label' => 'ENCARGADO TTPP (Zoom)',  'value' => $aud->encargado_ttp_zoom ?? null],
            'CTA_ZOOM'  => ['label' => 'CUENTA ZOOM',            'value' => $aud->cta_zoom ?? null],
            'ANFIT'     => ['label' => 'ANFITRIÓN',              'value' => $aud->anfitrion ?? null],
        ];

        switch ($tipo) {
            case 'AUDIENCIA CORTA':
                return [
                    $base['CTA_ZOOM'],
                    $base['JUEZP'],
                    $base['JUEZR'],
                    $base['JUEZI'],
                    $base['ENC_CAUSA'],
                    $base['ACTA'],
                    $base['ANFIT'],
                ];

            case 'LECTURA DE SENTENCIA':
            case 'LECTURA SENTENCIA':
            case 'LECTURA':
                return [
                    $base['CTA_ZOOM'],
                    $base['JUEZR'],
                    $base['ENC_CAUSA'],
                    $base['ACTA'],
                ];

            case 'JUICIO ORAL':
            case 'CONTINUACION JUICIO ORAL':
            case 'CONT. JUICIO ORAL':
            case 'CONTINUACIÓN JUICIO ORAL':
                return [
                    $base['DURACION'],
                    $base['ENC_TT'],
                    $base['ENC_TTZ'],
                    $base['TESTIGOS'],
                    $base['PERITOS'],
                    $base['DELITO'],
                    $base['INHABS'],
                    $base['ENC_CAUSA'],
                    $base['JUEZP'],
                    $base['JUEZR'],
                    $base['JUEZI'],
                    $base['ACTA'],
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

                // Estilos
                $borderThin = [
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
                ];
                $titleStyle = [
                    'font' => ['bold' => true, 'size' => 16],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ];
                $sectionHeaderStyle = [
                    'font' => ['bold' => true, 'size' => 12],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00E5A8']], // verde tipo captura
                ];
                $labelCellStyle = [
                    'font' => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ];
                $mutedStyle = ['font' => ['italic' => true, 'color' => ['rgb' => '9CA3AF']]];

                foreach (['A'=>16,'B'=>16,'C'=>20,'D'=>18,'E'=>18,'F'=>18,'G'=>18,'H'=>22] as $col=>$w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                $row = 1;
                $sheet->setCellValue("A{$row}", 'Programación de Audiencias 4°TOP Santiago');
                $sheet->mergeCells("A{$row}:H{$row}");
                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($titleStyle);
                $row++;

                $sheet->setCellValue("A{$row}", Carbon::parse($this->fecha)->format('d-m-Y'));
                $sheet->mergeCells("A{$row}:H{$row}");
                $row += 2;

                $audiencias = Audiencia::query()
                    ->whereDate('fecha', $this->fecha)
                    ->orderBy('sala')
                    ->orderBy('hora_inicio')
                    ->get();

                if ($audiencias->isEmpty()) {
                    $sheet->setCellValue("A{$row}", 'No se encontraron audiencias para la fecha seleccionada.');
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                    return;
                }

                foreach ($audiencias as $aud) {
                    $tipo = $this->normalizeTipo($aud->tipo_audiencia ?? '');

                    // Cabecera verde (incluye Duración en Juicio/Cont.)
                    $cabecera = sprintf(
                        'Sala %s -y %s -  %s Horas - %s - RIT %s - RUC %s%s',
                        (string)($aud->sala ?? '—'),
                        (string)($aud->cta_zoom ?? '—'),
                        optional($aud->hora_inicio instanceof \DateTimeInterface ? $aud->hora_inicio : Carbon::parse($aud->hora_inicio))->format('H:i'),
                        (string)($aud->tipo_audiencia ?? '—'),
                        (string)($aud->rit ?? '—'),
                        (string)($aud->ruc ?? '—'),
                        in_array($tipo, ['JUICIO ORAL','CONTINUACION JUICIO ORAL','CONT. JUICIO ORAL','CONTINUACIÓN JUICIO ORAL'], true)
                            ? (' - Duración: '.$this->dash($aud->duracion ?? null))
                            : ''
                    );
                    $sheet->setCellValue("A{$row}", $cabecera);
                    $sheet->mergeCells("A{$row}:H{$row}");
                    $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($sectionHeaderStyle);
                    $row++;

                    // Ficha por tipo (A..C etiqueta | D..H valor)
                    $start = $row;
                    foreach ($this->rowsFor($tipo, $aud) as $r) {
                        $sheet->setCellValue("A{$row}", $r['label']);
                        $sheet->mergeCells("A{$row}:C{$row}");
                        $sheet->getStyle("A{$row}:C{$row}")->applyFromArray($labelCellStyle);

                        $sheet->setCellValue("D{$row}", $this->dash($r['value']));
                        $sheet->mergeCells("D{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $row++;
                    }
                    $sheet->getStyle("A{$start}:H".($row-1))->getAlignment()->setWrapText(true);

                    $row++; // espacio

                    // ACUSADOS
                    $layout = $this->accusedLayout($tipo);
                    if ($layout === 'single') {
                        // Encabezado
                        $sheet->setCellValue("A{$row}", 'Acusados');
                        $sheet->mergeCells("A{$row}:E{$row}");
                        $sheet->setCellValue("F{$row}", 'Situación de Libertad');
                        $sheet->mergeCells("F{$row}:H{$row}");
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                        $row++;

                        $acusados = (array)($aud->acusados ?? []);
                        if (!empty($acusados)) {
                            foreach ($acusados as $ac) {
                                $sheet->setCellValue("A{$row}", $this->dash($ac['nombre_completo'] ?? null));
                                $sheet->mergeCells("A{$row}:E{$row}");
                                $sheet->setCellValue("F{$row}", $this->dash($ac['situacion'] ?? null));
                                $sheet->mergeCells("F{$row}:H{$row}");
                                $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                                $row++;
                            }
                        } else {
                            $sheet->setCellValue("A{$row}", '— Sin acusados —');
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                            $row++;
                        }
                    } else {
                        // Doble columna como en la captura de Juicio
                        $sheet->setCellValue("A{$row}", 'Acusados');
                        $sheet->mergeCells("A{$row}:C{$row}");
                        $sheet->setCellValue("D{$row}", 'Situación');
                        $sheet->setCellValue("E{$row}", 'Acusados');
                        $sheet->mergeCells("E{$row}:G{$row}");
                        $sheet->setCellValue("H{$row}", 'Situación');
                        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
                        $row++;

                        $acusados = array_values((array)($aud->acusados ?? []));
                        if (!empty($acusados)) {
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
                                $row++;
                            }
                        } else {
                            $sheet->setCellValue("A{$row}", '— Sin acusados —');
                            $sheet->mergeCells("A{$row}:H{$row}");
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($borderThin);
                            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray($mutedStyle);
                            $row++;
                        }
                    }

                    $row += 2; // separación entre audiencias
                }
            }
        ];
    }
}
