<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class InLayout extends Component
{
    public function __construct(public ?string $title = null) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.in');
    }
}
