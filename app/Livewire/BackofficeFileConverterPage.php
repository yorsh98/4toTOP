<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConvertsFiles;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BackofficeFileConverterPage extends Component
{
    use ConvertsFiles;

    protected function area(): string
    {
        return 'backoffice';
    }

    protected function authorizeConversion(): void
    {
        Gate::authorize('navbar.conversiones.visible');
    }

    public function mount(): void
    {
        $this->authorizeConversion();
    }
}
