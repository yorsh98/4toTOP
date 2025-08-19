<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guias;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class guiasController extends Controller
{
    public function index()
    {           
        return view('guias');
    }    
}
