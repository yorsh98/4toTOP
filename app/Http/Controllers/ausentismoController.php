<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ausentismoController extends Controller
{
   public function index()
    {
        return view('ausentismo');
    }
}
