<?php

namespace App\Http\Controllers;

use App\Exports\ProgramacionDiariaExport;
use Illuminate\Http\Request;

class AudienciasExportController extends Controller
{
    public function diaria(Request $request)
    {
        $validated = $request->validate([
            'fecha' => ['required', 'date_format:Y-m-d'],
        ]);

        $filename = 'Programacion_4TOP_'.$validated['fecha'].'.xlsx';

        return (new ProgramacionDiariaExport($validated['fecha']))->download($filename);
    }
}
