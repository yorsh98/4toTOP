<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\TtyppRegistro;
use App\Models\Turno;
use App\Services\BackofficeDocumentosCatalog;
use App\Services\FuncionarioCatalog;
use App\Services\WeatherService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TurnoController extends Controller
{
    public function __construct(private readonly FuncionarioCatalog $funcionarioCatalog) {}

    public function index()
    {
        Gate::authorize('turnos.ver');

        // Crear registros iniciales si no existen
        if (Turno::count() == 0) {
            Turno::create([]); // Crea el registro para Turno 1
            Turno::create([]); // Crea el registro para Turno 2
        }

        // Cargar datos actuales para mostrar en el formulario
        $turno1 = Turno::find(1);
        $turno2 = Turno::find(2);

        $magistrados = $this->funcionarioCatalog->nombresByContext('turnos.magistrados');
        $funcionarios = $this->funcionarioCatalog->nombresByContext('turnos.funcionarios');
        $jefaturas = $this->funcionarioCatalog->nombresByContext('turnos.jefatura');

        return view('turno', compact('turno1', 'turno2', 'magistrados', 'funcionarios', 'jefaturas'));

    }

    public function enviarTurno(BackofficeDocumentosCatalog $catalog, WeatherService $weatherService)
    {
        // Obtener los turnos desde la base de datos
        $turnos = Turno::findMany([1, 2])->keyBy('id');
        $turno1 = $turnos->get(1);
        $turno2 = $turnos->get(2);
        $encargadosAudiencia = $catalog->encargadosAudiencia();

        // Conteo por estado de las audiencias de hoy para el resumen de la portada
        $audienciasHoy = Audiencia::whereDate('fecha', Carbon::today())
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $clima = $weatherService->today();

        return view('welcome', compact('turno1', 'turno2', 'encargadosAudiencia', 'audienciasHoy', 'clima'));
    }

    public function update(Request $request, $id)
    {
        Gate::authorize('turnos.editar');

        $rules = [
            'FECHA' => ['nullable', 'string', 'max:255'],
        ];

        foreach (['TM1', 'TM2', 'TM3', 'ACDM1', 'ACDM2', 'ACDM3'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        foreach (['TF1', 'TF2', 'TF3', 'ACDF1', 'ACDF2', 'ACDF3'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        foreach (['TF1_CARGO', 'TF2_CARGO', 'TF3_CARGO'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        foreach (['TJ1', 'TJ2', 'TJ3', 'ACDJ1', 'ACDJ2', 'ACDJ3'] as $field) {
            $rules[$field] = ['nullable', 'string', 'max:255'];
        }

        $validatedData = $request->validate($rules);

        // Buscar el turno y actualizarlo
        $turno = Turno::findOrFail($id);
        $turno->update($validatedData);

        return redirect()->route('turno')->with('success', 'Turno actualizado correctamente');
    }

    public function ttyppWelcome(Request $request)
    {
        $tz = 'America/Santiago';
        $fecha = $request->query('fecha');

        if (! is_string($fecha) || validator(['fecha' => $fecha], ['fecha' => 'date_format:Y-m-d'])->fails()) {
            $fecha = null;
        }

        try {
            $selectedDate = $fecha
                ? Carbon::createFromFormat('Y-m-d', $fecha, $tz)->startOfDay()
                : Carbon::now($tz)->startOfDay();
        } catch (\Throwable $exception) {
            $selectedDate = Carbon::now($tz)->startOfDay();
        }

        $start = $selectedDate->copy()->startOfDay()->setTimezone('UTC');
        $end = $selectedDate->copy()->endOfDay()->setTimezone('UTC');

        $registros = TtyppRegistro::query()
            ->where(function ($query) use ($selectedDate, $start, $end) {
                $query->whereDate('fecha_asignacion', $selectedDate->toDateString())
                    ->orWhere(function ($legacy) use ($start, $end) {
                        $legacy->whereNull('fecha_asignacion')
                            ->whereBetween('created_at', [$start, $end]);
                    });
            })
            ->latest('id')
            ->get();

        return view('ttypp-welcome', [
            'fecha' => $selectedDate->toDateString(),
            'fechaHuman' => $selectedDate->locale('es')->isoFormat('dddd D [de] MMMM [de] YYYY'),
            'registros' => $registros,
        ]);
    }
}
