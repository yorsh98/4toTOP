<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ausenciasController extends Controller
{
    public function index()
    {
     return view('ausencias');   
    }
}
