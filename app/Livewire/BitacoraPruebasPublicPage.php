<?php

namespace App\Livewire;

use App\Models\BitacoraPruebaDia;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.in')]
class BitacoraPruebasPublicPage extends Component
{
    public string $selectedRit = '';

    public ?int $selectedDiaId = null;

    public function mount(): void
    {
        $firstRit = BitacoraPruebaDia::query()
            ->orderBy('rit')
            ->value('rit');

        $this->selectedRit = $firstRit ?? '';

        if ($this->selectedRit !== '') {
            $this->selectedDiaId = BitacoraPruebaDia::query()
                ->where('rit', $this->selectedRit)
                ->orderBy('numero_dia')
                ->value('id');
        }
    }

    public function selectRit(string $rit): void
    {
        $this->selectedRit = $rit;

        $this->selectedDiaId = BitacoraPruebaDia::query()
            ->where('rit', $rit)
            ->orderBy('numero_dia')
            ->value('id');
    }

    public function selectDia(int $diaId): void
    {
        $this->selectedDiaId = $diaId;
    }

    public function render()
    {
        $dias = collect();

        $rits = BitacoraPruebaDia::query()
            ->distinct()
            ->orderBy('rit')
            ->pluck('rit');

        if ($this->selectedRit !== '') {
            $dias = BitacoraPruebaDia::query()
                ->where('rit', $this->selectedRit)
                ->with(['items' => fn ($query) => $query->with('usuario')->orderBy('created_at')])
                ->orderBy('numero_dia')
                ->get();
        }

        $selectedDia = $dias->firstWhere('id', $this->selectedDiaId);

        return view('livewire.bitacora-pruebas-public-page', [
            'rits' => $rits,
            'dias' => $dias,
            'selectedDia' => $selectedDia,
        ]);
    }
}
