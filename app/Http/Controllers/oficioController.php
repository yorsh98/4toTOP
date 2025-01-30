<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Oficio; // <-- Importar tu modelo
use Yajra\DataTables\DataTables; // <-- Importar DataTables
use Illuminate\Support\Facades\Http;
use App\Services\OficioService;


class oficioController extends Controller
{
    public function index(OficioService $OficioService){
        $data = $OficioService->getOficios();
        return view('oficio',compact('data'));


    }

    public function delete($id, OficioService $OficioService, Request $request){
        //$url = $OficioService->__construct() ;
        $url = 'http://127.0.0.1:8001/api/Oficio/' . $id;
        $response = Http::delete($url);

        if ($response->successful()) {
            return redirect()->route('oficio')->with('success', 'Oficio eliminado correctamente.');
        } else {
            return redirect()->route('oficio')->with('error', 'No se pudo eliminar el oficio.');
        }
        //return redirect()->route('Oficio.delete');
    }
    
    public function update($id, Request $request){
    // URL de tu API REST
    $url = 'http://127.0.0.1:8001/api/Oficio/' . $id;

    // Datos que deseas actualiza
    $data = $request->only(['CausaAsig', 'UserSolicitante', 'UserDirigido']);

    // Enviar la solicitud PATCH
    $response = Http::patch($url, $data);

    // Comprobar la respuesta
    if ($response->successful()) {
        return redirect()->route('oficio')->with('success', 'Oficio actualizado correctamente.');
    }

    return redirect()->back()->with('error', 'Error al actualizar el oficio.');
    }

    public function getData(OficioService $OficioService){
             $data = $OficioService->getOficios();

            // Convertimos el array en un objeto para DataTables
            return DataTables::of(collect($data))
                ->addColumn('actions', function ($row) {
                    $updateBtn = '<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateOficioModal' . $row['id'] . '">Modificar</button>';
                    $deleteForm = '
                        <form action="' . route('Oficio.delete', $row['id']) . '" method="POST" style="display:inline;" onsubmit="return confirm(\'¿Estás seguro de eliminar este oficio?\');">
                            ' . csrf_field() . method_field('DELETE') . '
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>';
                    return $updateBtn . ' ' . $deleteForm;
                })
                ->rawColumns(['actions'])
                ->make(true);
    }




}

