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
        $pendientes = collect(session('visita_carcel.causas_pendientes', []))
            ->flatMap(fn (array $causa) => collect($causa['imputados'])->map(fn (array $imputado) => [
                'rit' => $causa['rit'], 'ruc' => $causa['ruc'], 'anio' => str($causa['rit'])->afterLast('-')->toString(),
                'nombre' => $imputado['nombre'], 'rut' => $imputado['rut'], 'hoja' => $imputado['hoja'], 'estado' => 'Pendiente de ingreso',
            ]));

        abort_if($comparacion->isEmpty() && $pendientes->isEmpty(), 422, 'Primero ejecuta una comparacion para exportar sus resultados.');

        $comparacion = $comparacion->concat($pendientes);

        return response()->streamDownload(function () use ($comparacion): void {
            $spreadsheet = new Spreadsheet;

            try {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Comparacion');
                $libroVisitaCarcel = $comparacion->contains(fn (array $causa) => isset($causa['nombre']));
                $headers = $libroVisitaCarcel
                    ? ['RIT', 'RUC', 'AÑO', 'IMPUTADO', 'RUT', 'HOJA', 'ESTADO']
                    : ['RIT', 'RUC', 'AÑO'];
                $sheet->fromArray([$headers], null, 'A1');

                foreach ($comparacion->sortBy(['rit', 'ruc'])->values() as $index => $causa) {
                    $sheet->fromArray($libroVisitaCarcel
                        ? [$causa['rit'], $causa['ruc'], $causa['anio'], $causa['nombre'], $causa['rut'], $causa['hoja'], $causa['estado']]
                        : [$causa['rit'], $causa['ruc'], $causa['anio']], null, 'A'.($index + 2));
                }

                $sheet->freezePane('A2');
                $lastColumn = $libroVisitaCarcel ? 'G' : 'C';
                $sheet->setAutoFilter("A1:{$lastColumn}1");
                foreach (range('A', $lastColumn) as $column) {
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
