<?php

namespace App\Http\Controllers;

use App\Models\TtyppRegistro;
use App\Support\SafeSpreadsheetValueBinder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TtyppExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        Gate::authorize('ttypp.exportar');

        $validated = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $selectedDate = Carbon::parse($validated['fecha'], 'America/Santiago');
        $start = $selectedDate->copy()->startOfDay()->setTimezone('UTC');
        $end = $selectedDate->copy()->endOfDay()->setTimezone('UTC');
        $search = trim($validated['search'] ?? '');

        $rows = TtyppRegistro::query()
            ->where(function ($query) use ($selectedDate, $start, $end) {
                $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                    ->orWhere(function ($legacy) use ($start, $end) {
                        $legacy->whereNull('fecha_asignacion')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('rit', 'like', "%{$search}%")
                        ->orWhere('ruc', 'like', "%{$search}%")
                        ->orWhere('acta', 'like', "%{$search}%")
                        ->orWhere('sala', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->get();

        return response()->streamDownload(function () use ($rows): void {
            $spreadsheet = new Spreadsheet;
            $spreadsheet->setValueBinder(new SafeSpreadsheetValueBinder);

            try {
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->fromArray([
                    ['RIT', 'RUC', 'Tipo AUD', 'N Dias', 'Cant.', 'Modalidad', 'Encargada TI MP', 'Encargada TI D', 'Encargada Zoom', 'Acta', 'Sala', 'Jueces', 'Fecha Asignacion', 'Creado', 'Actualizado'],
                ]);

                foreach ($rows as $index => $row) {
                    $sheet->fromArray([
                        $row->rit,
                        $row->ruc,
                        $row->tipo_audiencia,
                        $row->numero_dias,
                        $row->cantidad,
                        $row->modalidad,
                        $row->encargada_ti_mp,
                        $row->encargada_ti_d,
                        $row->encargada_zoom,
                        $row->acta,
                        $row->sala,
                        $row->jueces,
                        optional($row->fecha_asignacion)->format('Y-m-d'),
                        optional($row->created_at)->format('Y-m-d H:i:s'),
                        optional($row->updated_at)->format('Y-m-d H:i:s'),
                    ], null, 'A'.($index + 2));
                }

                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'ttypp_'.now()->format('Ymd_His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
