<?php

namespace App\Http\Controllers;

use App\Models\Penal;
use App\Models\VisitaCarcelImputado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class VisitaCarcelPenalesExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        Gate::authorize('visita_carcel.gestionar');

        $penales = Penal::query()->where('activo', true)->orderBy('nombre')->get();
        $porPenal = VisitaCarcelImputado::query()
            ->with('causa')
            ->whereIn('penal', $penales->pluck('nombre'))
            ->orderBy('penal')
            ->orderBy('nombre')
            ->get()
            ->groupBy('penal');
        $santiago = $penales->where('exportar_en_santiago', true)->filter(fn (Penal $penal) => $porPenal->has($penal->nombre));
        $individuales = $penales->where('exportar_en_santiago', false)->filter(fn (Penal $penal) => $porPenal->has($penal->nombre));

        return response()->streamDownload(function () use ($porPenal, $santiago, $individuales): void {
            $zipPath = tempnam(sys_get_temp_dir(), 'visita-carcel-');
            $zip = new ZipArchive;
            $temporales = [];

            if ($zipPath === false || $zip->open($zipPath, ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('No fue posible crear la exportación.');
            }

            try {
                if ($santiago->isNotEmpty()) {
                    $spreadsheet = new Spreadsheet;
                    $spreadsheet->removeSheetByIndex(0);

                    foreach ($santiago as $penal) {
                        $nombre = $this->nombreExportacion($penal->nombre);
                        $sheet = new Worksheet($spreadsheet, $this->tituloHoja($spreadsheet, $nombre));
                        $spreadsheet->addSheet($sheet);
                        $this->completarHoja($sheet, $nombre, $porPenal->get($penal->nombre));
                    }

                    $temporales[] = $this->agregarLibro($zip, $spreadsheet, 'Visita_Carcel_Santiago.xlsx');
                }

                foreach ($individuales as $penal) {
                    $spreadsheet = new Spreadsheet;
                    $sheet = $spreadsheet->getActiveSheet();
                    $sheet->setTitle('Visita Carcel');
                    $this->completarHoja($sheet, $penal->nombre, $porPenal->get($penal->nombre));
                    $nombreArchivo = (preg_replace('/[^A-Za-z0-9_-]+/', '_', $penal->nombre) ?: 'penal').'.xlsx';
                    $temporales[] = $this->agregarLibro($zip, $spreadsheet, $nombreArchivo);
                }
            } finally {
                $zip->close();
            }

            readfile($zipPath);

            foreach ($temporales as $temporal) {
                @unlink($temporal);
            }
            @unlink($zipPath);
        }, 'visita_carcel_penales_'.now()->format('Ymd_His').'.zip', ['Content-Type' => 'application/zip']);
    }

    private function completarHoja(Worksheet $sheet, string $penal, Collection $imputados): void
    {
        $sheet->mergeCells('A1:D1');
        $sheet->mergeCells('E1:J1');
        $sheet->mergeCells('K1:N1');
        $sheet->fromArray([['Lugar de Reclusion : '.$penal, null, null, null, 'Visita de Cárcel', null, null, null, null, null, now()->format('d/m/Y')]], null, 'A1');
        $sheet->getStyle('A1:N1')->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:N1')->getFill()->setFillType('solid')->getStartColor()->setRGB('4472C4');
        $sheet->fromArray([['Tribunal', 'Rit', 'Año', 'Ruc', 'Imputado', 'RUT', 'Delito', 'Fec. Ingreso P.P.', 'Plazo Cierre', 'Ultima Rev. P.P.', 'Juez', 'Defensor', 'Fec. Ult. Res.', 'Ult. Resolución']], null, 'A3');

        foreach ($imputados->values() as $index => $imputado) {
            [$rit, $anio] = array_pad(explode('-', $imputado->causa->rit, 2), 2, '');
            $sheet->fromArray(['4to TOP Santiago', $rit, $anio, $imputado->causa->ruc, $imputado->nombre, $imputado->rut, $imputado->delito, optional($imputado->fecha_ingreso_pp)->format('d/m/Y'), optional($imputado->plazo_cierre)->format('d/m/Y'), optional($imputado->ultima_rev_pp)->format('d/m/Y'), $imputado->juez, $imputado->defensor, optional($imputado->fecha_ult_res)->format('d/m/Y'), $imputado->ult_resolucion], null, 'A'.($index + 4));
        }

        $sheet->freezePane('A4');
        $sheet->setAutoFilter('A3:N3');
        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function agregarLibro(ZipArchive $zip, Spreadsheet $spreadsheet, string $nombre): string
    {
        $path = tempnam(sys_get_temp_dir(), 'penal-');

        if ($path === false) {
            throw new RuntimeException('No fue posible crear una planilla temporal.');
        }

        (new Xlsx($spreadsheet))->save($path);
        $zip->addFile($path, $nombre);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function nombreExportacion(string $penal): string
    {
        return match ($penal) {
            'CDP Stgo Uno' => 'CDP Santiago Uno',
            'CDP Stgo Sur' => 'CDP Santiago Sur',
            'Cuartel Gral. PDI Borgoño' => 'Cuartel General PDI Borgoño',
            default => $penal,
        };
    }

    private function tituloHoja(Spreadsheet $spreadsheet, string $nombre): string
    {
        $base = mb_substr(preg_replace('/[\\\\\/\?\*\[\]:]/u', '', $nombre) ?: 'Penal', 0, 31);
        $titulo = $base;
        $numero = 2;

        while ($spreadsheet->sheetNameExists($titulo)) {
            $sufijo = ' '.$numero++;
            $titulo = mb_substr($base, 0, 31 - mb_strlen($sufijo)).$sufijo;
        }

        return $titulo;
    }
}
