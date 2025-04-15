<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class docController extends Controller
{
    public function index()
    {
        return view('doc');
    }
}
