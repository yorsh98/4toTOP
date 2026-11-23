<?php

namespace App\View\Components;

use App\Services\Servicios\Maqueta\MaquetaPisoBuilder;
use Illuminate\View\Component;
use Illuminate\View\View;

class ServiciosMaqueta extends Component
{
    public function __construct(
        private MaquetaPisoBuilder $builder,
        public int $piso,
        public iterable $hotspots,
        public ?string $selectedHotspot = null,
    ) {}

    public function render(): View
    {
        $hotspots = is_array($this->hotspots) ? $this->hotspots : $this->hotspots->all();

        return view('components.servicios-maqueta', [
            'maqueta' => $this->builder->build($this->piso, $hotspots, $this->selectedHotspot),
        ]);
    }
}
