<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AudienciaEmail;

class prograController extends Controller{
    
    public function index(){
       
        return redirect()->route('progra.mostrar');
    }

    public function mostrar()
    {
        $contenidoHtml = '';

        $path = storage_path('app/ultima_audiencia_email.html');

        if (file_exists($path)) {
            $contenidoHtml = file_get_contents($path);
        }

        return view('progra', compact('contenidoHtml'));
    }
}
