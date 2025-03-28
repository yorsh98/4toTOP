<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Guias;
use Illuminate\Validation\Rule;
use Yajra\DataTables\DataTables;

class guiasController extends Controller
{
    public function index()
    {
        
        
        return view('guias');
    }

    public function datatable(Request $request) {
        return DataTables::of(Guias::query())
            ->addColumn('actions', function($guia) {
                return '<button class="btn btn-primary btn-edit" data-id="'.$guia->id.'">Editar</button>

                        <form action="'.route('guias.destroy', $guia->id).'" method="POST" style="display:inline;">
                            '.csrf_field().method_field("DELETE").'
                            <button type="submit" class="btn  btn-danger" onclick="return confirm(\'¿Estás seguro?\')">Eliminar</button>
                        </form>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
    
    public function store(Request $request)
    {
    $messages = [
        'nombre.required' => 'El campo nombre es obligatorio.',
        'rut.required' => 'Debe ingresar un RUT.',
        'rut.regex' => 'El formato del RUT es inválido (Ej: 123678-9).',
        'correo.email' => 'Debe ingresar un correo electrónico válido.',
        'tel1.digits_between' => 'El teléfono principal debe tener entre 9 y 12 dígitos.',
        'tel2.digits_between' => 'El teléfono opcional debe tener entre 9 y 12 dígitos.',
        'institucion.required' => 'Seleccione una institución.'
    ];

    $validated = $request->validate([
        'nombre' => 'required|string|max:255',
        'rut' => [
            'nullable', // ✅
            'string',
            'max:12',
            'regex:/^(\d{1,3}(?:\.?\d{3}){1,2}-[\dkK])$/i', // Acepta múltiples formatos
            Rule::unique('guias')->whereNull('deleted_at')
        ],
        'correo' => 'nullable|email|max:255',
        'tel1' => 'nullable|numeric|digits_between:9,12', // ✅
        'tel2' => 'nullable|numeric|digits_between:9,12',
        'institucion' => 'required|in:0,1,2,3,4,5,6,7,8,9,10,11' // id para busqueda mas rapida 
    ], $messages); //pasamos los mensajes personalizados

    Guias::create([
        'nombre_completo' => $validated['nombre'],
        'rut' => strtoupper($validated['rut'])?? null, // Normaliza a mayúsculas
        'email' => $validated['correo'] ?? null, // Acepta null
        'telefono1' => $validated['tel1']?? null,
        'telefono2' => $validated['tel2'] ?? null,
        'institucion' => $validated['institucion']
    ]);

    return redirect()->route('guias')->with('success', 'GUIA ingresada.');
    }

    public function edit($id)
    {
    $guia = Guias::findOrFail($id);
    return response()->json($guia);
    }   

    public function destroy($id)
    {
    $guia = Guias::findOrFail($id);
    $guia->delete();

    
    return redirect()->route('guias')->with('success', 'GUIA eliminada.');
    }
    
    public function update(Request $request, $id)
    {   
        $guia = Guias::findOrFail($id);
        $guia->update([
        'nombre_completo' => $request->nombre_completo,
        'rut' => $request->rut,
        'email' => $request->email,
        'telefono1' => $request->telefono1,
        'telefono2' => $request->telefono2,
        'institucion' => $request->institucion
    ]);

    return response()->json(['success' => 'Guía actualizada correctamente.']);
    }

    

}
