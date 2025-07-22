<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;

class AudienciaForm extends Component
{
    public $audienciaId;
    public $fecha;
    public $rit;
    public $sala;
    public $ubicacion;
    public $hora_inicio;
    public $ruc;
    public $cta_zoom;
    public $tipo_audiencia;
    public $num_testigos;
    public $num_peritos;
    public $duracion;
    public $delito;
    public $jueces_inhabilitados = [];
    public $encargado_causa;
    public $encargado_ttp;
    public $encargado_ttp_zoom;
    public $acta_sala;
    public $acusados = [];
    public $estado = 'POR_REALIZARSE';

    protected $listeners = ['editAudiencia'];
    
    protected $messages = [
        'fecha.required' => 'La fecha es obligatoria.',
        'rit.required' => 'El RIT es obligatorio.',
        'rit.unique' => 'Este RIT ya está registrado.',
        'hora_inicio.required' => 'La hora de inicio es obligatoria.',
        'delito.required' => 'Debe describir el delito.',
        'estado.required' => 'Seleccione un estado válido.',
        'estado.in' => 'El estado seleccionado no es válido.',
        'acusados.required' => 'Debe agregar al menos un acusado.',
        'acusados.min' => 'Debe agregar al menos un acusado.',
        'acusados.*.nombre_completo.required' => 'El nombre del acusado es obligatorio.',
        
    ];

    public function editAudiencia($id)
    {
        $audiencia = Audiencia::find($id);
        $this->audienciaId = $audiencia->id;
        $this->fecha = $audiencia->fecha->format('Y-m-d');
        $this->rit = $audiencia->rit;
        $this->sala = $audiencia->sala;
        $this->ubicacion = $audiencia->ubicacion;
        $this->hora_inicio = $audiencia->hora_inicio;
        $this->ruc = $audiencia->ruc;
        $this->cta_zoom = $audiencia->cta_zoom;
        $this->tipo_audiencia = $audiencia->tipo_audiencia;
        $this->num_testigos = $audiencia->num_testigos;
        $this->num_peritos = $audiencia->num_peritos;
        $this->duracion = $audiencia->duracion;
        $this->delito = $audiencia->delito;
        $this->jueces_inhabilitados = $audiencia->jueces_inhabilitados;
        $this->encargado_causa = $audiencia->encargado_causa;
        $this->encargado_ttp = $audiencia->encargado_ttp;
        $this->encargado_ttp_zoom = $audiencia->encargado_ttp_zoom;
        $this->acta_sala = $audiencia-> acta_sala; 
        $this->acusados = $audiencia->acusados;
    }

    public function addAcusado()
    {
        $this->acusados[] = [
            'nombre_completo' => '',
            'situacion' => 'LIBRE',
            'medida_cautelar' => '',
            'forma_notificacion' => ''
        ];
    }

    public function removeAcusado($index)
    {
        unset($this->acusados[$index]);
        $this->acusados = array_values($this->acusados);
    }

    public function save()
    {
        $validated = $this->validate(Audiencia::rules());

        Audiencia::updateOrCreate(
            ['id' => $this->audienciaId],
            $validated
        );

        $this->reset();
        session()->flash('message', 'Audiencia guardada correctamente');
    }

    public function render()
    {
        return view('livewire.audiencia-form');
    }
}