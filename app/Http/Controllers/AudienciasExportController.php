<?php

namespace App\Http\Controllers;

use App\Exports\ProgramacionDiariaExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;

class AudienciasExportController extends Controller
{
    public function diaria(Request $request)
    {
        Gate::authorize('audiencias.exportar');

        $fecha = $request->query('fecha'); // YYYY-MM-DD
        abort_unless($fecha, 400, 'Falta fecha');
        $filename = 'Programacion_4TOP_'.$fecha.'.xlsx';

        return Excel::download(new ProgramacionDiariaExport($fecha), $filename);
    }
}
