<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Audiencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha', 'rit', 'sala', 'ubicacion', 'hora_inicio', 
        'ruc', 'cta_zoom', 'tipo_audiencia', 'num_testigos',
        'num_peritos', 'duracion', 'delito', 'jueces_inhabilitados',
        'encargado_causa', 'encargado_ttp', 'encargado_ttp_zoom',
        'acusados', 'estado', 'acta', 'JuezP', 'JuezR', 'JuezI', 'anfitrion', 'obs',
    ];

    protected $casts = [
        'jueces_inhabilitados' => 'array',
        'acusados' => 'array',
        'fecha' => 'date',
        'hora_inicio' => 'datetime:H:i'
    ];

    public static function rules()
    {
        return [
            'fecha' => 'required|date',
            'rit'  => 'required|string',
            'sala' => 'required',
            'ubicacion' => 'nullable|string',
            'hora_inicio' => 'required',
            'ruc' => 'required',
            'cta_zoom' => 'required|string',
            'tipo_audiencia' => 'required|string',
            'num_testigos' => 'nullable|integer',
            'num_peritos' => 'nullable|integer',
            'duracion' => 'nullable|string',
            'delito' => 'nullable|string',
            'jueces_inhabilitados' => 'nullable|string',
            'encargado_causa' => 'required|string', 
            'encargado_ttp' => 'nullable|string',
            'encargado_ttp_zoom' => 'nullable|string',
            'acusados' => 'required|array|min:1',
            'acusados.*.nombre_completo' => 'required|string',
            'acusados.*.situacion' => 'required|string',
            'acusados.*.medida_cautelar' => 'nullable|string',
            'acusados.*.forma_notificacion' => 'nullable|string',
            'estado' => 'required|in:POR_REALIZARSE,EN_CURSO,RECESO,FINALIZADA',
            'acta' => 'required|string',
            'JuezP' => 'nullable|string',
            'JuezR' => 'required|string',
            'JuezI' => 'nullable|string',
            'anfitrion' => 'nullable|string',
            'obs' => 'nullable|string'
        ];
        
    }
}