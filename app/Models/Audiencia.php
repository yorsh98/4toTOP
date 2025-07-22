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
        'acusados', 'estado', 'acta_sala'
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
            'rit' => 'required|string|unique:audiencias,rit',
            'sala' => 'string',
            'hora_inicio' => 'required',
            'tipo_audiencia' => 'required|string',
            'delito' => 'required|string',
            'estado' => 'required|in:POR_REALIZARSE,EN_CURSO,RECESO,FINALIZADA',
            'acusados' => 'required|array|min:1',
            'acusados.*.nombre_completo' => 'required|string',
            'acusados.*.situacion' => 'in:LIBRE,DETENIDO,MEDIDAS_CAUTELARES',
            'acusados.*.medida_cautelar' => 'string',
            'acusados.*.forma_notificacion' => 'string'
        ];
    }
}