<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use App\Services\OService;
use App\Services\LService;


class SistOficioLibertadesController extends Controller
{
    public function index()
    {
        //get al Oficio
        return view('SistOficioLibertades');

        //get a libertades
    }
    public function create(){
        return view('SistOficioLibertades');
    }
    public function store(Request $request){
        //$url=env('API_URL', 'http://127.0.0.1');
        /*$response = Http::post('http://127.0.0.1:8001/api/Oficio', [
            'CausaAsig' => $request->input('CausaAsig'),
            'UserSolicitante' => $request->input('UserSolicitante'),
            'UserDirigido' => $request->input('UserDirigido'),
        ]);
        return redirect()->route('SistOficioLibertades.store');*/
        $response = Http::post('http://127.0.0.1:8001/api/Oficio', [
            'CausaAsig' => $request->input('CausaAsig'),
            'UserSolicitante' => $request->input('UserSolicitante'),
            'UserDirigido' => $request->input('UserDirigido'),
        ]);
    
        if ($response->successful()) {
            return redirect()->route('SistOficioLibertades.index')->with('success', 'Oficio enviado correctamente');
        } else {
            Log::error('Error al enviar el oficio', ['status' => $response->status(), 'body' => $response->body()]);
            return redirect()->route('SistOficioLibertades.index')->with('error', 'Error al enviar el oficio');
        }
    }
    
}



