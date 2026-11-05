<?php

namespace App\Http\Controllers;

use App\Support\SafeSpreadsheetValueBinder;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class VisitaCarcelExportController extends Controller
{
    public function __invoke(): BinaryFileResponse
    {
        Gate::authorize('visita_carcel.gestionar');

        $comparacion = collect(session('visita_carcel.comparacion', []));
        $esLibro = session('visita_carcel.tipo_comparacion') === 'libro'
            || (session('visita_carcel.tipo_comparacion') === null && $comparacion->contains(fn (array $fila) => array_key_exists('nombre', $fila)));
        $pendientes = $esLibro
            ? collect(session('visita_carcel.causas_pendientes', []))
                ->flatMap(fn (array $causa) => collect($causa['imputados'] ?? [])->map(fn (array $imputado) => [
                    'rit' => $causa['rit'] ?? '', 'ruc' => $causa['ruc'] ?? '', 'anio' => str($causa['rit'] ?? '')->afterLast('-')->toString(),
                    'nombre' => $imputado['nombre'] ?? '', 'rut' => $imputado['rut'] ?? '', 'hoja' => $imputado['hoja'] ?? '', 'estado' => 'Pendiente de ingreso', 'tipo' => 'Causa nueva',
                ]))
                ->concat(collect(session('visita_carcel.imputados_pendientes', []))->map(fn (array $imputado) => [
                    'rit' => $imputado['rit'] ?? '', 'ruc' => $imputado['ruc'] ?? '', 'anio' => str($imputado['rit'] ?? '')->afterLast('-')->toString(),
                    'nombre' => $imputado['nombre'] ?? '', 'rut' => $imputado['rut'] ?? '', 'hoja' => $imputado['hoja'] ?? '', 'estado' => 'Pendiente de ingreso', 'tipo' => 'Imputado en causa existente',
                ]))
            : collect();

        abort_if($comparacion->isEmpty() && $pendientes->isEmpty(), 422, 'Primero ejecuta una comparacion para exportar sus resultados.');

        $comparacion = $comparacion->concat($pendientes);
        $path = tempnam(sys_get_temp_dir(), 'visita-carcel-comparacion-');

        if ($path === false) {
            throw new RuntimeException('No fue posible crear el archivo temporal para la exportación.');
        }

        $spreadsheet = new Spreadsheet;
        $spreadsheet->setValueBinder(new SafeSpreadsheetValueBinder);

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Comparacion');
            $headers = $esLibro
                ? ['RIT', 'RUC', 'AÑO', 'IMPUTADO', 'RUT', 'HOJA', 'ESTADO', 'TIPO']
                : ['RIT', 'RUC', 'AÑO'];
            $sheet->fromArray([$headers], null, 'A1');

            foreach ($comparacion->sortBy([['rit', 'asc'], ['ruc', 'asc']])->values() as $index => $causa) {
                $sheet->fromArray($esLibro
                    ? [$causa['rit'] ?? '', $causa['ruc'] ?? '', $causa['anio'] ?? '', $causa['nombre'] ?? '', $causa['rut'] ?? '', $causa['hoja'] ?? '', $causa['estado'] ?? '', $causa['tipo'] ?? 'Coincidencia']
                    : [$causa['rit'] ?? '', $causa['ruc'] ?? '', $causa['anio'] ?? ''], null, 'A'.($index + 2));
            }

            $sheet->freezePane('A2');
            $lastColumn = $esLibro ? 'H' : 'C';
            $sheet->setAutoFilter("A1:{$lastColumn}1");
            foreach (range('A', $lastColumn) as $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
            }

            (new Xlsx($spreadsheet))->save($path);
        } catch (Throwable $exception) {
            unlink($path);

            throw $exception;
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        return response()->download($path, 'visita_carcel_comparacion_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
