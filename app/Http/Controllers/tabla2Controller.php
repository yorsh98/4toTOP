<?php

namespace App\Http\Controllers;

use App\Services\LibertadesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class tabla2Controller extends Controller
{
    public function index(LibertadesService $LibertadesService)
    {
        //get al Oficio
        //return view('SistOficioLibertades');  esto lo tenia desde el inicio para que se viera esta ruta al iniciar el link

        $data = $LibertadesService->getLibertad();
        return view('tabla2',compact('data'));


           // $response = Http::get('http://127.0.0.1:8001/api/Oficio/');
            //$data = $response->json("oficio");
            
    
 
    }
}


