<?php

namespace App\Http\Controllers;

use App\Models\Guias;
use Illuminate\Http\Request;
use Yajra\DataTables\DataTables;

class guiastelefonicasController extends Controller
{
    public function index()
    {
        return view('guiastelefonicas');
    }

    public function getGuias(Request $request)
    {
        $query = Guias::query();

        // Si se recibe un filtro por institución, aplicarlo
        if ($request->has('institucion') && ! empty($request->institucion)) {
            $query->where('institucion', $request->institucion);
        }

        return DataTables::of($query)->make(true);
    }
}
