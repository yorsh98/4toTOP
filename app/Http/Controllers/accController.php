<?php

namespace App\Http\Controllers;

use App\Models\AccesoDirecto;

class accController extends Controller
{
    public function index()
    {
        $accesos = AccesoDirecto::query()->where('activo', true)->ordenados()->get();

        return view('acc', [
            'grupos' => $accesos->groupBy('grupo'),
            'total' => $accesos->count(),
        ]);
    }
}
