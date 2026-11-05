<?php

namespace App\Http\Controllers;

use App\Models\Activo;
use App\Support\SafeSpreadsheetValueBinder;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ServiciosInventarioExportController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        Gate::authorize('servicios.ver');

        $activos = Activo::query()
            ->with('sector')
            ->join('sectors', 'sectors.id', '=', 'activos.sector_id')
            ->select('activos.*')
            ->orderBy('sectors.piso')
            ->orderBy('sectors.nombre')
            ->orderBy('activos.nombre')
            ->get();

        return response()->streamDownload(function () use ($activos): void {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->setValueBinder(new SafeSpreadsheetValueBinder);

            try {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Inventario');
                $sheet->fromArray([['Nombre', 'Código inventario', 'Número serie', 'Estado', 'Piso', 'Sector', 'Observaciones']]);

                foreach ($activos as $index => $activo) {
                    $sheet->fromArray([
                        $activo->nombre,
                        $activo->codigo_inventario,
                        $activo->numero_serie,
                        $activo->estado,
                        $activo->sector?->piso === 'SUBTERRANEO' ? 'Subterráneo' : 'Piso '.(int) $activo->sector?->piso,
                        $activo->sector?->nombre,
                        $activo->observaciones,
                    ], null, 'A'.($index + 2));
                }

                $sheet->freezePane('A2');
                $sheet->setAutoFilter('A1:G1');
                $sheet->getStyle('A1:G1')->getFont()->setBold(true);

                foreach (['A' => 34, 'B' => 20, 'C' => 20, 'D' => 16, 'E' => 16, 'F' => 32, 'G' => 50] as $column => $width) {
                    $sheet->getColumnDimension($column)->setWidth($width);
                }

                $sheet->getStyle('G2:G'.($activos->count() + 1))->getAlignment()->setWrapText(true);

                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'inventario_servicios_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
