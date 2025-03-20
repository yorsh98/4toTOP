<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Funcionario;


class addfuncionarioController extends Controller
{

    public function index()
    {
        $solicitantes = Funcionario::orderBy('nombre')->get();
        return view('addfuncionario', compact('solicitantes'));
    }

    public function store(Request $request)
    {
        // Validar la entrada del formulario
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        // Guardar el nuevo funcionario en la base de datos
        Funcionario::create([
            'nombre' => $request->input('nombre'),
        ]);

        return redirect()->route('addfuncionario')->with('success', 'Funcionario agregado correctamente.');
    }

    public function enviarSolicitantes()
    {
        // Obtener los funcionarios desde la base de datos
        $solicitantes = Funcionario::orderby('nombre')->get();

        
        return view('SistOficioLibertades', compact('solicitantes'));
    }

    public function destroy($id)
    {
    // Buscar el funcionario por su ID
    $funcionario = Funcionario::findOrFail($id);

    // Eliminar el funcionario
    $funcionario->delete();

    // Redirigir de nuevo con un mensaje de éxito
    return redirect()->route('addfuncionario')->with('success', 'Funcionario eliminado correctamente.');
    }

}
