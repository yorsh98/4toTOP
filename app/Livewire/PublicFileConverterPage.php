<?php

namespace App\Livewire;

use App\Livewire\Concerns\ConvertsFiles;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.in')]
class PublicFileConverterPage extends Component
{
    use ConvertsFiles;
}
