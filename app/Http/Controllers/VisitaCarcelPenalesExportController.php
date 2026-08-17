<?php

namespace App\Http\Controllers;

use App\Models\VisitaCarcelImputado;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class VisitaCarcelPenalesExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        Gate::authorize('visita_carcel.gestionar');

        $porPenal = VisitaCarcelImputado::query()->with('causa')->orderBy('penal')->orderBy('nombre')->get()->groupBy('penal');

        return response()->streamDownload(function () use ($porPenal): void {
            $zipPath = tempnam(sys_get_temp_dir(), 'visita-carcel-');
            $zip = new ZipArchive;
            $zip->open($zipPath, ZipArchive::OVERWRITE);

            try {
                foreach ($porPenal as $penal => $imputados) {
                    $spreadsheet = new Spreadsheet;
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('Visita Carcel');
                    $sheet->mergeCells('A1:D1');
                    $sheet->mergeCells('E1:J1');
                    $sheet->mergeCells('K1:N1');
                    $sheet->fromArray([['Lugar de Reclusion : '.$penal, null, null, null, 'Visita de Cárcel', null, null, null, null, null, now()->format('d/m/Y')]], null, 'A1');
                    $sheet->getStyle('A1:N1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
                    $sheet->getStyle('A1:N1')->getFill()->setFillType('solid')->getStartColor()->setRGB('4472C4');
                    $sheet->fromArray([['Tribunal', 'Rit', 'Año', 'Ruc', 'Imputado', 'RUT', 'Delito', 'Fec. Ingreso P.P.', 'Plazo Cierre', 'Ultima Rev. P.P.', 'Juez', 'Defensor', 'Fec. Ult. Res.', 'Ult. Resolución']], null, 'A3');

                    foreach ($imputados as $index => $imputado) {
                        [$rit, $anio] = array_pad(explode('-', $imputado->causa->rit, 2), 2, '');
                        $sheet->fromArray(['4to TOP Santiago', $rit, $anio, $imputado->causa->ruc, $imputado->nombre, $imputado->rut, $imputado->delito, optional($imputado->fecha_ingreso_pp)->format('d/m/Y'), optional($imputado->plazo_cierre)->format('d/m/Y'), optional($imputado->ultima_rev_pp)->format('d/m/Y'), $imputado->juez, $imputado->defensor, optional($imputado->fecha_ult_res)->format('d/m/Y'), $imputado->ult_resolucion], null, 'A'.($index + 4));
                    }

                    $sheet->freezePane('A4');
                    $sheet->setAutoFilter('A3:N3');
                    foreach (range('A', 'N') as $column) {
                        $sheet->getColumnDimension($column)->setAutoSize(true);
                    }
                    $path = tempnam(sys_get_temp_dir(), 'penal-').'.xlsx';
                    (new Xlsx($spreadsheet))->save($path);
                    $zip->addFile($path, preg_replace('/[^A-Za-z0-9_-]+/', '_', $penal).'.xlsx');
                    $spreadsheet->disconnectWorksheets();
                }
            } finally {
                $zip->close();
            }

            readfile($zipPath);
            @unlink($zipPath);
        }, 'visita_carcel_penales_'.now()->format('Ymd_His').'.zip', ['Content-Type' => 'application/zip']);
    }
}
