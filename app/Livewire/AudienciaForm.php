<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;


class AudienciaForm extends Component
{
    public $audienciaId;
    public $fecha;
    public $rit;
    public $sala;
    public $ubicacion;
    public $hora_inicio = '09:00';
    public $ruc;
    public $cta_zoom;
    public $tipo_audiencia;
    public $num_testigos = 0;
    public $num_peritos = 0;
    public $duracion;
    public $delito;
    public $encargado_causa;
    public $encargado_ttp;
    public $encargado_ttp_zoom;
    public $estado = 'POR_REALIZARSE';
    public $JuezP;
    public $JuezR;
    public $JuezI;
    public $acta;
    public $anfitrion;
    public $obs;

    //campo para juez
    public $jueces_inhabilitados = [];
    public $Nuevosjueces_inhabilitados = [
        'nombre_completo' => 'no'
    ]; 

    // Campos para acusados
    public $acusados = [];
    public $nuevoAcusado = [
        'nombre_completo' => '',
        'situacion' => '',
        'medida_cautelar' => '',
        'forma_notificacion' => ''
    ];
    public $tiposdelibertad = [
        'Libre',
        'P.Prev.',
        'P.x OC.',        
    ];

    // Tipos de audiencia sugeridos
    public $tiposAudiencia = [
        'Juicio Oral',
        'Cont. Juicio Oral',
        'Audiencia Corta',
        'Lectura de Sentencia'
    ];

    protected $listeners = ['editAudiencia'];
    
    protected $messages = [
        'fecha.required' => 'La fecha es obligatoria.',
        'rit.required' => 'El RIT es obligatorio.',
        'ruc.required' => 'El RUC es obligatorio.',
        'sala.required' => 'La sala es obligatoria.',
        'acta.required' => 'El Acta es obligatorio.',
        'ubicacion.required' => 'La ubicación es obligatoria.',
        'hora_inicio.required' => 'La hora de inicio es obligatoria.',
        'tipo_audiencia.required' => 'El tipo de audiencia es obligatorio.',        
        'delito.required' => 'El delito es obligatorio.',                
        'acusados.required' => 'Debe agregar al menos un acusado.',
        'nuevoAcusado.nombre_completo.required' => 'El nombre del acusado es obligatorio.',
        'nuevoAcusado.situacion' => 'Favor ingresar la situacion del acusado',
        'JuezR.required' => 'El Juez Redactor es obligatorio.',   
        'cta_zoom' => 'La cuenta ZOOM es obligatoria',
        'encargado_causa' => 'El encargado de causa es obligatorio',
        'Nuevosjueces_inhabilitados' => 'Debe agregar al menos un magistrado si no puede agregar a NO'
    ];

    public function mount()
    {   
        if (!Auth::check()) {
            abort(403, 'No autorizado.');
        }
        $this->fecha = now()->addDay()->format('Y-m-d');
    }

    public function editAudiencia($id)
    {   
        if (!Auth::check()) {
            abort(403, 'No autorizado.');
        }
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
        $this->jueces_inhabilitados = $audiencia->jueces_inhabilitados ?? [];
        //$this->jueces_inhabilitados = is_string($audiencia->jueces_inhabilitados)
        //? json_decode($audiencia->jueces_inhabilitados, true) ?? []
        //: ($audiencia->jueces_inhabilitados ?? []);
        $this->encargado_causa = $audiencia->encargado_causa;
        $this->encargado_ttp = $audiencia->encargado_ttp;
        $this->encargado_ttp_zoom = $audiencia->encargado_ttp_zoom;
        $this->estado = $audiencia->estado;
        $this->acusados = $audiencia->acusados ?? [];
        //$this->acusados = is_string($audiencia->acusados)
        //? json_decode($audiencia->acusados, true) ?? []
        //: ($audiencia->acusados ?? []);
        $this->JuezP = $audiencia->JuezP;
        $this->JuezR = $audiencia->JuezR;
        $this->JuezI = $audiencia->JuezI;
        $this->acta = $audiencia->acta;
        $this->anfitrion = $audiencia->anfitrion;
        $this->obs = $audiencia->obs;
    }

    public function agregarAcusado()
    {
        $this->validate([
            'nuevoAcusado.nombre_completo' => 'required',
            'nuevoAcusado.situacion' => 'required',
            
        ]);

        $this->acusados[] = $this->nuevoAcusado;
        $this->reset('nuevoAcusado');
    }

    public function eliminarAcusado($index)
    {
        unset($this->acusados[$index]);
        $this->acusados = array_values($this->acusados);
    }

    public function Agregarjueces_inhabilitados()
    {
        $this->validate([
            'Nuevosjueces_inhabilitados.nombre_completo' => 'required',
        ]);

        $this->jueces_inhabilitados[] = $this->Nuevosjueces_inhabilitados;
        $this->reset('Nuevosjueces_inhabilitados');
    }

    public function eliminarjueces_inhabilitados($index)
    {
        unset($this->jueces_inhabilitados[$index]);
        $this->jueces_inhabilitados = array_values($this->jueces_inhabilitados);
    }

    public function guardarAudiencia()
    {   
        if (!Auth::check()) {
            abort(403, 'No autorizado.');
        }
        $validated = $this->validate([
            'fecha' => 'required|date',           
            'rit' => ['required',Rule::unique('audiencias')->where(function ($query) {
                return $query->where('rit', $this->rit)
                            ->whereDate('fecha', $this->fecha)
                            ->where('tipo_audiencia', $this->tipo_audiencia);
            })->ignore($this->audienciaId)
        ],
            'hora_inicio' => 'required',
            'sala' => 'required',
            'ruc' => 'required',
            'tipo_audiencia' => 'required',
            'cta_zoom' => 'required',
            'encargado_causa' => 'required',
            'acta' => 'required',
            'acusados' => 'required|array|min:1',
            'nuevoAcusado.nombre_completo' => 'required_if:acusados,[]',            
            'JuezR' => 'required',                       
        ]);

        $data = [
            'fecha' => $this->fecha,
            'rit' => $this->rit,
            'sala' => $this->sala,
            'ubicacion' => $this->ubicacion,
            'hora_inicio' => $this->hora_inicio,
            'ruc' => $this->ruc,
            'cta_zoom' => $this->cta_zoom,
            'tipo_audiencia' => $this->tipo_audiencia,
            'num_testigos' => $this->num_testigos,
            'num_peritos' => $this->num_peritos,
            'duracion' => $this->duracion,
            'delito' => $this->delito,
            'jueces_inhabilitados' => $this->jueces_inhabilitados,
            'encargado_causa' => $this->encargado_causa,
            'encargado_ttp' => $this->encargado_ttp,
            'encargado_ttp_zoom' => $this->encargado_ttp_zoom,
            'estado' => $this->estado,
            'acusados' => $this->acusados,
            'JuezP' => $this->JuezP,
            'JuezR' => $this->JuezR,
            'JuezI' => $this->JuezI,
            'acta' => $this->acta,
            'anfitrion' => $this->anfitrion,
            'obs' => $this->obs
        ];

        if (!empty($this->nuevoAcusado['nombre_completo'])) {
            $this->agregarAcusado();
        }

        Audiencia::create($data);

        $this->reset();
        $this->fecha = now()->addDay()->format('Y-m-d');
        $this->dispatch('alerta-success');
    }

    public function buscarPorRit()
    {
        if (!empty($this->rit)) {
            $audienciaExistente = Audiencia::where('rit', $this->rit)->first();
            
            if ($audienciaExistente) {
                $this->editAudiencia($audienciaExistente->id);
                // Opcional: Mostrar mensaje informativo
                session()->flash('info', 'Se cargó una audiencia existente. Puedes modificar los datos y se guardará como nueva versión.');
            }
        }
    }


    public function render()
    {   if (!Auth::check()) {
            abort(403, 'No autorizado.');
        }
        return view('livewire.audiencia-form');
    }
}