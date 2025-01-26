<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;

class SistOficioLibertadesController extends Controller
{
    public function index()
    {
        //get al Oficio
        return view('SistOficioLibertades');

        //get a libertades
    }

   
}




