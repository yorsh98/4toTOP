<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ServiciosPlano extends Component
{
    public function __construct(
        public int $piso,
        public iterable $hotspots,
        public ?string $selectedHotspot = null,
    ) {}

    public function render(): View
    {
        return view('components.servicios-plano');
    }
}
