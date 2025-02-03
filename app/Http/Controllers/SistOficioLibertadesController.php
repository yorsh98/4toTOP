<?php

namespace App\Http\Controllers;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Http;
use App\Models\Funcionario;
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
    
    public function store(Request $request)
    {
        // Obtener los nombres de los funcionarios válidos
        $validSolicitantes = Funcionario::pluck('nombre')->toArray();

        // Validar los datos del formulario
        $validated = $request->validate([
            'tipo' => ['required', 'in:oficio,libertad'], // Solo permite 'oficio' o 'libertad'
            'CausaAsig' => ['required', 'string', 'regex:/^[a-zA-Z0-9\s\-]{1,10}$/'], // Letras, números, espacios y guiones medios (-)
            'UserSolicitante' => ['required', 'string', 'in:' . implode(',', $validSolicitantes)], // Valores permitidos
            'UserDirigido' => ['required', 'string', 'regex:/^[a-zA-Z0-9\s\-]+$/'], // Letras, números, espacios y guiones medios (-)
        ], [
            'tipo.in' => 'El tipo de solicitud no es válido.',
            'CausaAsig.regex' => 'El campo CAUSA ASIGNADA solo permite letras, números, espacios y guiones medios (-).',
            'UserSolicitante.in' => 'El SOLICITANTE seleccionado no es válido.',
            'UserDirigido.regex' => 'El campo MOTIVO solo permite letras, números, espacios y guiones medios (-).',
        ]);

        // Seleccionar la URL de la API según el tipo de solicitud
        $url = $validated['tipo'] === 'oficio'
            ? 'http://127.0.0.1:8001/api/Oficio'
            : 'http://127.0.0.1:8001/api/Libertad';
        
            try {
                // Enviar los datos validados a la API
                $response = Http::post($url, [
                    'CausaAsig' => $validated['CausaAsig'],
                    'UserSolicitante' => $validated['UserSolicitante'],
                    'UserDirigido' => $validated['UserDirigido'],
                ]);
    
                if ($response->successful()) {
                    // Extraer las variables de la respuesta de la API
                    $data = $response->json();
                    $numEntregado = $data[$validated['tipo']]['Numentregado'] ?? null;
                    $año = $data[$validated['tipo']]['año'] ?? null;
    
                    // Pasar los valores a la vista
                    return redirect()->route('SistOficioLibertades.index')->with([
                        'success' => 'Oficio/Libertad enviado correctamente',
                        'NumEntregado' => $numEntregado,
                        'año' => $año,
                    ]);
                } else {
                    Log::error('Error al enviar el oficio o libertad', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return redirect()->route('SistOficioLibertades.index')->with('error', 'Error al enviar la libertad u oficio.');
                }
            } catch (\Exception $e) {
                // Manejar errores de la API
                Log::error('Error de conexión con la API', ['message' => $e->getMessage()]);
                return redirect()->route('SistOficioLibertades.index')->with('error', 'Error de conexión con la API.');
            }
        }
    
}



