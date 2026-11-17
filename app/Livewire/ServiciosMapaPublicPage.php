<?php

namespace App\Livewire;

use Livewire\Attributes\Layout;

#[Layout('layouts.in')]
class ServiciosMapaPublicPage extends ServiciosMapaPage
{
    public function render()
    {
        return view('livewire.servicios-mapa-publica', parent::render()->getData());
    }

    protected function queryString(): array
    {
        return [
            'piso' => ['history' => true],
        ];
    }

    public function boot(): void
    {
        $this->publicMode = true;
    }

    public function mount(): void
    {
        $this->publicMode = true;

        $piso = request()->integer('piso', 8);

        if (in_array($piso, [7, 8, 9], true)) {
            $this->piso = $piso;
        }

        $this->search = trim((string) request()->query('search', ''));

        parent::mount();
        $this->moveToMatchingFloor();
    }

    protected function authorizeBackoffice(): void
    {
        // Public microsite actions must not require backoffice permissions.
    }
}
