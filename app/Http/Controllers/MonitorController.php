<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Gate;

class MonitorController extends Controller
{
    public function index()
    {
        Gate::authorize('audiencias.monitor.ver');

        return view('Monitor');
    }
}
