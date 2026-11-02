<?php

namespace App\Http\Controllers;

use App\Services\FuncionarioCatalog;
use Illuminate\View\View;

class CumpleanosController extends Controller
{
    public function index(FuncionarioCatalog $catalog): View
    {
        $today = now('America/Santiago');
        $people = collect(range(1, 12))->flatMap(fn (int $month) => $catalog->cumpleanerosDelMes($month))
            ->map(function (array $person) use ($catalog, $today): array {
                $date = $catalog->fechaCelebracion($person['fecha_cumpleanos'], $today->year);

                return ['nombre' => $person['nombre'], 'fecha' => $date->locale('es')->isoFormat('DD [de] MMMM'), 'orden' => $date->format('m-d'), 'hoy' => $date->isSameDay($today)];
            })->sortBy('orden')->values()->map(fn (array $person) => collect($person)->except('orden')->all());

        return view('cumpleanos', ['people' => $people]);
    }
}
