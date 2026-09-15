<?php

namespace App\Http\Controllers;

// <-- Importar tu modelo
use Yajra\DataTables\DataTables; // <-- Importar DataTables

class oficiolibertadController extends Controller
{
    public function index()
    {
        return view('oficiolibertad');
    }
}
