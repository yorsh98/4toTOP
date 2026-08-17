<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VisitaCarcelExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        Gate::authorize('visita_carcel.gestionar');

        $comparacion = collect(session('visita_carcel.comparacion', []));

        abort_if($comparacion->isEmpty(), 422, 'Primero ejecuta una comparacion para exportar sus coincidencias.');

        return response()->streamDownload(function () use ($comparacion): void {
            $spreadsheet = new Spreadsheet;

            try {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Comparacion');
                $sheet->fromArray([['RIT', 'RUC', 'AÑO']], null, 'A1');

                foreach ($comparacion->sortBy(['rit', 'ruc'])->values() as $index => $causa) {
                    $sheet->fromArray([$causa['rit'], $causa['ruc'], $causa['anio']], null, 'A'.($index + 2));
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:C1');
                foreach (range('A', 'C') as $column) {
                    $sheet->getColumnDimension($column)->setAutoSize(true);
                }

                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'visita_carcel_comparacion_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
