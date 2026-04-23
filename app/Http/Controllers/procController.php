<?php

namespace App\Http\Controllers;

use App\Services\BackofficeDocumentosCatalog;

class procController extends Controller
{
    public function index(BackofficeDocumentosCatalog $catalog)
    {
        return view('proc', [
            'procedimientos' => $catalog->procedimientosPublicos(),
        ]);
    }
}
