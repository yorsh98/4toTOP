<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class procController extends Controller
{
    public function index()
    {
        return view('proc');
    }
}
