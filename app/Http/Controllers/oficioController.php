<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oficio; // <-- Importar tu modelo
use Yajra\DataTables\DataTables; // <-- Importar DataTables
use Illuminate\Support\Facades\Http;
use App\Services\OficioService;


class oficioController extends Controller
{
    public function index(OficioService $OficioService){
        $data = $OficioService->getOficios();
        return view('oficio',compact('data'));


    }

    public function delete($id, OficioService $OficioService, Request $request){
        //$url = $OficioService->__construct() ;
        $url = 'http://127.0.0.1:8001/api/Oficio/' . $id;
        $response = Http::delete($url);

        if ($response->successful()) {
            return redirect()->route('oficio')->with('success', 'Oficio eliminado correctamente.');
        } else {
            return redirect()->route('oficio')->with('error', 'No se pudo eliminar el oficio.');
        }
        //return redirect()->route('Oficio.delete');
    }


}

