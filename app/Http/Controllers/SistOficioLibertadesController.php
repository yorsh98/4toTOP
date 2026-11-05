<?php

namespace App\Http\Controllers;

use App\Models\Libertad;
use App\Models\Oficio;
use App\Services\FuncionarioCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class SistOficioLibertadesController extends Controller
{
    public function __construct(private readonly FuncionarioCatalog $funcionarioCatalog) {}

    public function index()
    {
        $solicitantes = $this->funcionarioCatalog->funcionariosByContext('oficios.solicitantes');

        return view('SistOficioLibertades', [
            'solicitantes' => $solicitantes,
        ]);
    }

    public function create()
    {
        $solicitantes = $this->funcionarioCatalog->funcionariosByContext('oficios.solicitantes');

        return view('SistOficioLibertades', [
            'solicitantes' => $solicitantes,
        ]);
    }

    public function store(Request $request)
    {
        $validSolicitantes = $this->funcionarioCatalog->nombresByContext('oficios.solicitantes');

        if ($validSolicitantes === []) {
            return redirect()
                ->route('SistOficioLibertades.index')
                ->with('error', 'No hay funcionarios habilitados para solicitar numeros.');
        }

        $validated = $request->validate([
            'tipo' => ['required', 'in:oficio,libertad'],
            'CausaAsig' => ['required', 'string', 'regex:/^[a-zA-Z0-9\s\-]{1,10}$/'],
            'UserSolicitante' => ['required', 'string', Rule::in($validSolicitantes)],
            'UserDirigido' => ['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-]+$/'],
        ], [
            'tipo.in' => 'El tipo de solicitud no es válido.',
            'CausaAsig.regex' => 'CAUSA ASIGNADA permite letras/números/espacios/guion (-), máx 10.',
            'UserSolicitante.in' => 'El SOLICITANTE seleccionado no es válido.',
            'UserDirigido.regex' => 'MOTIVO permite letras/números/espacios/guion (-).',
        ]);

        // Año por defecto según America/Santiago (tu preferencia)
        $anio = now('America/Santiago')->year;

        // Selecciona modelo según el tipo
        $modelClass = $validated['tipo'] === 'oficio' ? Oficio::class : Libertad::class;

        try {
            $registro = DB::transaction(function () use ($modelClass, $validated, $anio) {
                // 1) ¿Existe un número borrado (soft) de este año para reutilizar?
                /** @var Model|Builder $modelClass */
                $candidato = $modelClass::onlyTrashed()
                    ->where('año', $anio)
                    ->orderBy('Numentregado')
                    ->lockForUpdate()
                    ->first();

                if ($candidato) {
                    // Restaurar y actualizar campos
                    $candidato->restore();
                    $candidato->fill([
                        'CausaAsig' => $validated['CausaAsig'],
                        'UserSolicitante' => $validated['UserSolicitante'],
                        'UserDirigido' => $validated['UserDirigido'],
                    ])->save();

                    return $candidato;
                }

                // 2) Si no hay candidato, correlativo max+1 entre activos del año
                $max = $modelClass::whereNull('deleted_at')
                    ->where('año', $anio)
                    ->max('Numentregado');

                $nuevo = new $modelClass;
                $nuevo->Numentregado = $max ? $max + 1 : 1;
                $nuevo->año = $anio;
                $nuevo->CausaAsig = $validated['CausaAsig'];
                $nuevo->UserSolicitante = $validated['UserSolicitante'];
                $nuevo->UserDirigido = $validated['UserDirigido'];
                $nuevo->save();

                return $nuevo;
            }, 3);

            return redirect()
                ->route('SistOficioLibertades.index')
                ->with([
                    'success' => 'Solicitud ingresada correctamente.',
                    'tipo' => $validated['tipo'], // para el título del modal
                    'NumEntregado' => $registro->Numentregado,
                    'año' => $registro->año,
                ]);

        } catch (\Throwable $e) {
            Log::error('Fallo en store SistOficioLibertades', ['msg' => $e->getMessage()]);

            return redirect()
                ->route('SistOficioLibertades.index')
                ->with('error', 'No fue posible registrar la solicitud. Intenta nuevamente.');
        }
    }

    public function enviarSolicitud(Request $request)
    {
        return $this->store($request);
    }
}
