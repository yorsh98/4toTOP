<?php

namespace App\Http\Controllers;

use App\Models\Funcionario;
use App\Services\FuncionarioCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class addfuncionarioController extends Controller
{
    public function __construct(private readonly FuncionarioCatalog $funcionarioCatalog) {}

    public function index()
    {
        $solicitantes = Funcionario::orderBy('nombre')->get();
        $roles = $this->funcionarioCatalog->roleOptions();

        return view('addfuncionario', compact('solicitantes', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rol' => ['required', Rule::in($this->funcionarioCatalog->roles())],
            'email' => ['required', 'email', 'max:255', 'unique:funcionarios,email'],
            'fecha_cumpleanos' => ['required', 'date'],
        ]);

        Funcionario::create($validated);

        return redirect()->route('addfuncionario.index')->with('success', 'Funcionario agregado correctamente.');
    }

    public function enviarSolicitantes()
    {
        $solicitantes = $this->funcionarioCatalog->funcionariosByContext('oficios.solicitantes');

        return view('SistOficioLibertades', compact('solicitantes'));
    }

    public function destroy(int $id)
    {
        $funcionario = Funcionario::findOrFail($id);

        $funcionario->delete();

        return redirect()->route('addfuncionario.index')->with('success', 'Funcionario eliminado correctamente.');
    }
}
