<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\welcome;
use App\Models\Turno;


class welcomeController extends Controller
{
    public function index()
    {
        
      
        return view('welcome');
        
    }

    /*public function mostrarTurnos()
    {
    // Obtener todos los turnos o específicamente el turno 1 y 2
    $turno1 = Turno::find(1);
    $turno2 = Turno::find(2);
    
    // Pasarlos a la vista
    return view('welcome', compact('turno1', 'turno2'));
    }*/
    
}
