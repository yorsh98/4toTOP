<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;

#[Layout('layouts.in')]
class BitacoraPruebasPublicPage extends BitacoraPruebasViewer
{
    public function render()
    {
        return view('livewire.bitacora-pruebas-publica', parent::render()->getData());
    }
}
