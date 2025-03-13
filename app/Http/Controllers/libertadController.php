<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Libertad; // <-- Importar tu modelo
use Yajra\DataTables\DataTables; // <-- Importar DataTables
use Illuminate\Support\Facades\Http;
use App\Services\LibertadesService;


class libertadController extends Controller
{
    public function index(LibertadesService $LibertadesService){
        $data = $LibertadesService->getLibertad();
        return view('libertad',compact('data'));


    }

    public function delete($id){
        $url = 'http://10.13.214.129:8082/api/Libertad/' . $id;
        $response = Http::delete($url);

        if ($response->successful()) {
            return redirect()->route('libertad')->with('success', 'Libertad eliminado correctamente.');
        } else {
            return redirect()->route('libertad')->with('error', 'No se pudo eliminar la Libertad.');
        }
        
    }
    
    public function update($id, Request $request){
    // URL de tu API REST
    $url = 'http://10.13.214.129:8082/api/Libertad/' . $id;

    // Datos que deseas actualiza
    $data = $request->only(['CausaAsig', 'UserSolicitante', 'UserDirigido']);

    // Enviar la solicitud PATCH
    $response = Http::patch($url, $data);

    // Comprobar la respuesta
    if ($response->successful()) {
        return redirect()->route('libertad')->with('success', 'Libertad actualizado correctamente.');
    }

    return redirect()->back()->with('error', 'Error al actualizar la libertad.');
    }

    public function getData(LibertadesService $LibertadesService){
             $data = $LibertadesService->getLibertad();

            // Convertimos el array en un objeto para DataTables
            return DataTables::of(collect($data))
                ->addColumn('actions', function ($row) {
                    $updateBtn = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateLibertadModal' . $row['id'] . '">Modificar</button>';
                    $deleteForm = '
                        <form action="' . route('Libertad.delete', $row['id']) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'¿Estás seguro de eliminar esta Libertad?\');">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>';
                    return $updateBtn . ' ' . $deleteForm;
                })
                ->rawColumns(['actions'])
                ->make(true);
    }




}

