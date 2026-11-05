<?php

namespace App\Livewire;

use App\Models\BitacoraPruebaDia;
use Livewire\Attributes\Locked;
use Livewire\Component;

class BitacoraPruebasViewer extends Component
{
    #[Locked]
    public bool $showHero = true;

    #[Locked]
    public string $selectedRit = '';

    #[Locked]
    public ?int $selectedDiaId = null;

    public string $searchRit = '';

    public string $searchDia = '';

    public string $searchDetalle = '';

    public function mount(bool $showHero = true): void
    {
        $this->showHero = $showHero;

        $firstRit = BitacoraPruebaDia::query()
            ->orderBy('rit')
            ->value('rit');

        $this->selectedRit = trim((string) ($firstRit ?? ''));

        if ($this->selectedRit !== '') {
            $this->selectedDiaId = BitacoraPruebaDia::query()
                ->where('rit', $this->selectedRit)
                ->orderBy('numero_dia')
                ->value('id');
        }
    }

    public function selectRit(string $rit): void
    {
        $rit = trim($rit);
        if (! BitacoraPruebaDia::query()->where('rit', $rit)->exists()) {
            return;
        }

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
        $this->searchRit = mb_substr($this->searchRit, 0, 100);
        $this->searchDia = mb_substr($this->searchDia, 0, 100);
        $this->searchDetalle = mb_substr($this->searchDetalle, 0, 100);
        $dias = collect();
        $matchesPorDia = [];

        $ritData = BitacoraPruebaDia::query()
            ->select('rit', 'enlace_acta')
            ->orderBy('rit')
            ->get()
            ->groupBy('rit');

        $enlacesPorRit = $ritData
            ->map(function ($rows) {
                $enlace = $rows
                    ->pluck('enlace_acta')
                    ->filter(fn ($value) => is_string($value) && trim($value) !== '')
                    ->first();

                return $enlace ? trim((string) $enlace) : null;
            })
            ->all();

        $rits = $ritData
            ->keys()
            ->map(fn ($rit) => trim((string) $rit))
            ->filter(fn (string $rit) => $rit !== '')
            ->unique()
            ->values();

        if (trim($this->searchRit) !== '') {
            $needle = mb_strtolower(trim($this->searchRit));
            $rits = $rits
                ->filter(fn (string $rit) => str_contains(mb_strtolower($rit), $needle))
                ->values();
        }

        if ($this->selectedRit !== '') {
            $dias = BitacoraPruebaDia::query()
                ->where('rit', $this->selectedRit)
                ->with(['items' => fn ($query) => $query->with('usuario')->orderBy('created_at')])
                ->orderBy('numero_dia')
                ->get();

            if (trim($this->searchDia) !== '') {
                $needle = mb_strtolower(trim($this->searchDia));
                $dias = $dias
                    ->filter(function (BitacoraPruebaDia $dia) use ($needle) {
                        $diaLabel = 'dia '.(string) $dia->numero_dia;
                        $fecha = $dia->fecha_dia?->format('d-m-Y') ?? '';

                        return str_contains(mb_strtolower($diaLabel), $needle)
                            || str_contains(mb_strtolower($fecha), $needle);
                    })
                    ->values();
            }

            if (trim($this->searchDetalle) !== '') {
                $needle = mb_strtolower(trim($this->searchDetalle));

                $dias = $dias
                    ->map(function (BitacoraPruebaDia $dia) use ($needle, &$matchesPorDia) {
                        $filteredItems = $dia->items
                            ->filter(function ($item) use ($needle) {
                                $nombre = mb_strtolower((string) $item->nombre_prueba);
                                $institucion = mb_strtolower((string) $item->institucion_exhibe);
                                $usuario = mb_strtolower((string) ($item->usuario?->name ?? ''));

                                return str_contains($nombre, $needle)
                                    || str_contains($institucion, $needle)
                                    || str_contains($usuario, $needle);
                            })
                            ->values();

                        $matchesPorDia[$dia->id] = $filteredItems->count();

                        $dia->setRelation('items', $filteredItems);

                        return $dia;
                    })
                    ->filter(fn (BitacoraPruebaDia $dia) => ($matchesPorDia[$dia->id] ?? 0) > 0)
                    ->values();
            }
        }

        if ($dias->isNotEmpty() && ! $dias->contains(fn (BitacoraPruebaDia $dia) => $dia->id === $this->selectedDiaId)) {
            $this->selectedDiaId = (int) $dias->first()->id;
        }

        $selectedDia = $dias->firstWhere('id', $this->selectedDiaId);

        return view('livewire.bitacora-pruebas-viewer', [
            'rits' => $rits,
            'dias' => $dias,
            'selectedDia' => $selectedDia,
            'enlacesPorRit' => $enlacesPorRit,
            'matchesPorDia' => $matchesPorDia,
        ]);
    }
}
