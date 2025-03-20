<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Turno;

class TurnoController extends Controller
{
    public function index()
    {
    // Crear registros iniciales si no existen
    if (Turno::count() == 0) {
        Turno::create([]); // Crea el registro para Turno 1
        Turno::create([]); // Crea el registro para Turno 2
        }
    
    // Cargar datos actuales para mostrar en el formulario
    $turno1 = Turno::find(1);
    $turno2 = Turno::find(2);
    
    return view('turno', compact('turno1', 'turno2'));
    
    }

    public function enviarTurno()
{
    // Obtener los turnos desde la base de datos
    $turno1 = Turno::find(1);
    $turno2 = Turno::find(2);

    // Verifica si los turnos existen antes de pasarlos a la vista
    return view('welcome', compact('turno1', 'turno2'));
}

    public function update(Request $request, $id)
    {
        // Validar los datos
        $validatedData = $request->validate([
            'TM1' => 'nullable|string', 'TM2' => 'nullable|string', 'TM3' => 'nullable|string',
            'TF1' => 'nullable|string', 'TF2' => 'nullable|string', 'TF3' => 'nullable|string',
            'TJ1' => 'nullable|string', 'TJ2' => 'nullable|string', 'TJ3' => 'nullable|string',
            'ACDM1' => 'nullable|string', 'ACDM2' => 'nullable|string', 'ACDM3' => 'nullable|string',
            'ACDF1' => 'nullable|string', 'ACDF2' => 'nullable|string', 'ACDF3' => 'nullable|string',
            'ACDJ1' => 'nullable|string', 'ACDJ2' => 'nullable|string', 'ACDJ3' => 'nullable|string',
            'FECHA' => 'nullable|string'
        ]);

        // Buscar el turno y actualizarlo
        $turno = Turno::findOrFail($id);
        $turno->update($validatedData);

        
        return redirect()->route('turno')->with('success', 'Turno actualizado correctamente');
    }
}
