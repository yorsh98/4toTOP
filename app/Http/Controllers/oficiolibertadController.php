<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oficio; // <-- Importar tu modelo
use Yajra\DataTables\DataTables; // <-- Importar DataTables
use Illuminate\Support\Facades\Http;
use App\Services\OficioService;


class oficiolibertadController extends Controller
{
    public function index()
    {
        return view('oficiolibertad');
    }
}

