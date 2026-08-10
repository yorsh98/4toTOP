<?php

namespace App\Livewire;

use App\Models\Activo;
use App\Models\Sector;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class ServiciosMapaPage extends Component
{
    #[Locked]
    public bool $publicMode = false;

    public int $piso = 8;

    public ?int $selectedSectorId = null;

    public ?string $selectedHotspotIdentifier = null;

    public string $search = '';

    public function mount(): void
    {
        $this->authorizeBackoffice();
    }

    public function selectSector(int $sectorId): void
    {
        $this->authorizeBackoffice();

        $sector = Sector::query()->findOrFail($sectorId);

        $this->selectedSectorId = $sector->id;
        $this->selectedHotspotIdentifier = $sector->identificador_mapa;
    }

    public function selectHotspot(string $identifier): void
    {
        $this->authorizeBackoffice();

        $this->selectedHotspotIdentifier = $identifier;
        $this->selectedSectorId = Sector::query()
            ->where('piso', $this->floorLabel())
            ->where('identificador_mapa', $identifier)
            ->value('id');
    }

    public function selectPiso(int $piso): void
    {
        $this->authorizeBackoffice();

        if (! in_array($piso, [7, 8, 9], true)) {
            return;
        }

        $this->piso = $piso;
        $this->closeSectorDetail();
    }

    public function closeSectorDetail(): void
    {
        $this->selectedSectorId = null;
        $this->selectedHotspotIdentifier = null;
    }

    public function render()
    {
        $sectores = Sector::query()
            ->where('piso', $this->floorLabel())
            ->with(['activos' => fn ($query) => $query->latest('id')])
            ->withCount('activos')
            ->orderBy('nombre')
            ->get()
            ->keyBy('identificador_mapa');

        $selectedSector = $this->selectedSectorId
            ? $sectores->firstWhere('id', $this->selectedSectorId)
            : null;

        $activos = Activo::query()
            ->with('sector')
            ->whereHas('sector', fn ($query) => $query->where('piso', $this->floorLabel()))
            ->when($this->search !== '', function ($query) {
                $search = "%{$this->search}%";

                $query->where(function ($query) use ($search) {
                    $query->where('nombre', 'like', $search)
                        ->orWhere('codigo_inventario', 'like', $search)
                        ->orWhere('numero_serie', 'like', $search)
                        ->orWhere('observaciones', 'like', $search)
                        ->orWhereHas('sector', function ($query) use ($search) {
                            $query->where('nombre', 'like', $search)
                                ->orWhere('identificador_mapa', 'like', $search);
                        });
                });
            })
            ->latest('id')
            ->limit(200)
            ->get();

        $publicActivos = $this->publicMode
            ? Activo::query()
                ->with('sector')
                ->when($this->search !== '', function ($query) {
                    $search = "%{$this->search}%";

                    $query->where(function ($query) use ($search) {
                        $query->where('nombre', 'like', $search)
                            ->orWhere('estado', 'like', $search)
                            ->orWhereHas('sector', function ($query) use ($search) {
                                $query->where('nombre', 'like', $search)
                                    ->orWhere('identificador_mapa', 'like', $search)
                                    ->orWhere('piso', 'like', $search);
                            });
                    });
                })
                ->latest('id')
                ->limit(300)
                ->get()
            : collect();

        return view('livewire.servicios-mapa-page', [
            'hotspots' => $this->hotspots($sectores),
            'sectoresSinPosicion' => $sectores->except(array_column($this->hotspotLayout(), 'identificador')),
            'selectedSector' => $selectedSector,
            'selectedHotspot' => $this->selectedHotspot($sectores),
            'totalSectores' => $sectores->count(),
            'totalActivos' => $sectores->sum('activos_count'),
            'activos' => $activos,
            'publicActivos' => $publicActivos,
            'imagePath' => asset("images/planos/Plano_Piso_{$this->piso}.png"),
            'availableFloors' => [7, 8, 9],
            'isPublic' => $this->publicMode,
        ]);
    }

    protected function authorizeBackoffice(): void
    {
        if ($this->publicMode) {
            return;
        }

        Gate::authorize('servicios.ver');
    }

    private function hotspots($sectores)
    {
        return collect($this->hotspotLayout())
            ->map(function (array $hotspot) use ($sectores) {
                $sector = $sectores->get($hotspot['identificador']);

                return [
                    ...$hotspot,
                    'sector' => $sector,
                    'activos_count' => $sector?->activos_count ?? 0,
                    'has_data' => $sector !== null && $sector->activos_count > 0,
                ];
            })
            ->values();
    }

    private function selectedHotspot($sectores): ?array
    {
        if (! $this->selectedHotspotIdentifier) {
            return null;
        }

        return $this->hotspots($sectores)
            ->firstWhere('identificador', $this->selectedHotspotIdentifier);
    }

    private function floorLabel(): string
    {
        return match ($this->piso) {
            7 => '7MO',
            9 => '9NO',
            default => '8VO',
        };
    }

    private function hotspotLayout(): array
    {
        if ($this->piso !== 8) {
            return [];
        }

        return [
            ['identificador' => '837', 'label' => '837 Erick .A.', 'x' => 31, 'y' => 41, 'w' => 100, 'h' => 140],
            ['identificador' => '836', 'label' => '836 P. Brundl', 'x' => 31, 'y' => 181, 'w' => 159, 'h' => 79],
            ['identificador' => '835', 'label' => '835 M. Garcia', 'x' => 31, 'y' => 260, 'w' => 159, 'h' => 69],
            ['identificador' => '834', 'label' => '929 / 834 Claudia M.', 'x' => 31, 'y' => 329, 'w' => 159, 'h' => 70],
            ['identificador' => '838', 'label' => '838 Cocina', 'x' => 288, 'y' => 41, 'w' => 138, 'h' => 102],
            ['identificador' => '839', 'label' => '839 Banos', 'x' => 426, 'y' => 41, 'w' => 142, 'h' => 102],
            ['identificador' => '812', 'label' => 'SALA 803 812', 'x' => 311, 'y' => 211, 'w' => 217, 'h' => 186],
            ['identificador' => '806', 'label' => 'SALA 802 806', 'x' => 527, 'y' => 211, 'w' => 203, 'h' => 186],
            ['identificador' => 'APUBLICO', 'label' => 'A.PUBLICO', 'x' => 810, 'y' => 211, 'w' => 148, 'h' => 186],
            ['identificador' => '803', 'label' => 'SALA 801 803', 'x' => 1014, 'y' => 211, 'w' => 386, 'h' => 194],
            ['identificador' => '823', 'label' => '823 Bano', 'x' => 142, 'y' => 461, 'w' => 77, 'h' => 115],
            ['identificador' => '814', 'label' => '814 Bano', 'x' => 142, 'y' => 576, 'w' => 77, 'h' => 112],
            ['identificador' => '811', 'label' => '811 Bod comput.', 'x' => 288, 'y' => 461, 'w' => 69, 'h' => 115],
            ['identificador' => '810', 'label' => '810 Cocina', 'x' => 357, 'y' => 461, 'w' => 69, 'h' => 115],
            ['identificador' => '809', 'label' => '809 Bano', 'x' => 426, 'y' => 461, 'w' => 67, 'h' => 115],
            ['identificador' => '808', 'label' => '808 Bano', 'x' => 493, 'y' => 461, 'w' => 68, 'h' => 115],
            ['identificador' => '807', 'label' => '807 Bod oficina', 'x' => 561, 'y' => 461, 'w' => 79, 'h' => 115],
            ['identificador' => '815', 'label' => '815 Bod.', 'x' => 288, 'y' => 576, 'w' => 69, 'h' => 112],
            ['identificador' => '816', 'label' => '816 Bano', 'x' => 357, 'y' => 576, 'w' => 69, 'h' => 112],
            ['identificador' => '817', 'label' => '817 Bano', 'x' => 426, 'y' => 576, 'w' => 67, 'h' => 112],
            ['identificador' => '818', 'label' => '818 Bod resma', 'x' => 493, 'y' => 576, 'w' => 68, 'h' => 112],
            ['identificador' => '819', 'label' => '819 Bod agua', 'x' => 561, 'y' => 576, 'w' => 79, 'h' => 112],
            ['identificador' => '820', 'label' => '820 Bod aseo', 'x' => 640, 'y' => 576, 'w' => 80, 'h' => 112],
            ['identificador' => '805', 'label' => '805 Bod.', 'x' => 1048, 'y' => 461, 'w' => 68, 'h' => 115],
            ['identificador' => '804', 'label' => '804 Bod.', 'x' => 1116, 'y' => 461, 'w' => 68, 'h' => 115],
            ['identificador' => '821', 'label' => '821 Bod.', 'x' => 1048, 'y' => 576, 'w' => 68, 'h' => 112],
            ['identificador' => '822', 'label' => '822 Bod anillado', 'x' => 1116, 'y' => 576, 'w' => 68, 'h' => 112],
            ['identificador' => '802', 'label' => '802 Bano', 'x' => 1275, 'y' => 461, 'w' => 68, 'h' => 115],
            ['identificador' => '801', 'label' => '801 Bano', 'x' => 1343, 'y' => 461, 'w' => 57, 'h' => 115],
            ['identificador' => '803_BANO', 'label' => '803 Bano', 'x' => 1275, 'y' => 576, 'w' => 125, 'h' => 112],
            ['identificador' => 'HALL CENTRAL', 'label' => 'HALL CENTRAL', 'x' => 720, 'y' => 398, 'w' => 328, 'h' => 346],
            ['identificador' => '833', 'label' => '833 Rene Bonnemaison', 'x' => 31, 'y' => 744, 'w' => 99, 'h' => 171],
            ['identificador' => '832', 'label' => '832 Pedro Suarez', 'x' => 130, 'y' => 744, 'w' => 101, 'h' => 171],
            ['identificador' => '831', 'label' => '831 Cristian Soto', 'x' => 231, 'y' => 744, 'w' => 93, 'h' => 171],
            ['identificador' => '830', 'label' => '830 Cristian Fuentealba', 'x' => 324, 'y' => 744, 'w' => 96, 'h' => 171],
            ['identificador' => '829', 'label' => '829', 'x' => 420, 'y' => 744, 'w' => 108, 'h' => 171],
            ['identificador' => '828', 'label' => '828 Sala Deliberac', 'x' => 528, 'y' => 744, 'w' => 101, 'h' => 171],
            ['identificador' => '827', 'label' => '827 Luis Werner', 'x' => 629, 'y' => 744, 'w' => 102, 'h' => 171],
            ['identificador' => '826', 'label' => '826 Eduardo Fredes', 'x' => 1015, 'y' => 744, 'w' => 136, 'h' => 171],
            ['identificador' => '825', 'label' => '825 Sala Ninos', 'x' => 1151, 'y' => 744, 'w' => 136, 'h' => 171],
            ['identificador' => '823_OF', 'label' => '823 Amelia Munoz', 'x' => 1287, 'y' => 744, 'w' => 113, 'h' => 171],
            ['identificador' => 'TTYPP DEFENSA', 'label' => 'TTYPP DEFENSA', 'x' => 640, 'y' => 41, 'w' => 760, 'h' => 120],
            ['identificador' => 'FUERA MAG 835', 'label' => 'FUERA MAG 835', 'x' => 190, 'y' => 329, 'w' => 95, 'h' => 70],
        ];
    }
}
