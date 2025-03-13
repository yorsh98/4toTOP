<?php

namespace App\Http\Controllers;

use App\Services\OficioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


class tablasController extends Controller
{
    public function index(OficioService $OficioService)
    {
        //get al Oficio
        //return view('SistOficioLibertades');  esto lo tenia desde el inicio para que se viera esta ruta al iniciar el link

        $data = $OficioService->getOficios();
        return view('tablas',compact('data'));


           // $response = Http::get('http://10.13.214.129:8082/api/Oficio/');
            //$data = $response->json("oficio");
            
    
 
    }
}
