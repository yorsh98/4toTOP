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
        
        return view('SistOficioLibertades');
        
    }
    public function create(){
        return view('SistOficioLibertades');
    }
    public function store(Request $request){

        //CREATE DE OFICIO/LIBERTAD 
                $tipo = $request->input('tipo');

            // Seleccionar la URL de la API según el tipo de solicitud
            $url = $tipo === 'oficio' 
            ? 'http://127.0.0.1:8001/api/Oficio' 
            : 'http://127.0.0.1:8001/api/Libertad';
            
        $response = Http::post($url, [
            'CausaAsig' => $request->input('CausaAsig'),
            'UserSolicitante' => $request->input('UserSolicitante'),
            'UserDirigido' => $request->input('UserDirigido'),
        ]);
    
        if ($response->successful()) {

            // Extraer las variables de la respuesta de la API
        $data = $response->json();
        $numEntregado = $data[$tipo]['Numentregado'] ?? null;
        $año = $data[$tipo]['año'] ?? null;

        // Pasar los valores a la vista
        return redirect()->route('SistOficioLibertades.index')->with([
            'success' => 'Oficio/Libertad enviado correctamente',
            'NumEntregado' => $numEntregado,
            'año' => $año
        ]);
            return redirect()->route('SistOficioLibertades.index')->with('success', 'libertad enviado correctamente');
            // Extraer las variables de la respuesta de la API
                
        } else {
            Log::error('Error al enviar el oficio o libertad', ['status' => $response->status(), 'body' => $response->body()]);
            return redirect()->route('SistOficioLibertades.index')->with('error', 'Error al enviar la libertad u oficio');
        }


    }
    
}



