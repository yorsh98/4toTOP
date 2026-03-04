<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class buscadorsentenciasController extends Controller
{
    public function index()
    {
        return view('buscadorsentencias');
    }
}
