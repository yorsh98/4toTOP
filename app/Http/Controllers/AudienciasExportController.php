<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Exports\ProgramacionDiariaExport;
use Maatwebsite\Excel\Facades\Excel;

class AudienciasExportController extends Controller
{
    public function diaria(Request $request)
    {
    $fecha = $request->query('fecha'); // YYYY-MM-DD
    abort_unless($fecha, 400, 'Falta fecha');
    $filename = 'Programacion_4TOP_'.$fecha.'.xlsx';
    return Excel::download(new ProgramacionDiariaExport($fecha), $filename);
    }
}