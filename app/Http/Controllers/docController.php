<?php

namespace App\Http\Controllers;

use App\Services\BackofficeDocumentosCatalog;

class docController extends Controller
{
    public function index(BackofficeDocumentosCatalog $catalog)
    {
        return view('doc', [
            'doc' => $catalog->documentos(),
        ]);
    }
}
