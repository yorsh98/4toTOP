<?php

namespace App\Livewire;

use App\Models\Activo;
use App\Models\InsumoExistencia;
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

    public string $mapSearch = '';

    public string $inventorySearch = '';

    #[Locked]
    public ?int $editingSectorId = null;

    public string $sectorNombre = '';

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
        $this->cancelSectorEdit();
    }

    public function editSector(int $sectorId): void
    {
        Gate::authorize('servicios.editar');

        $sector = Sector::query()->findOrFail($sectorId);

        $this->editingSectorId = $sector->id;
        $this->sectorNombre = $sector->nombre;
        $this->resetValidation();
    }

    public function saveSector(): void
    {
        Gate::authorize('servicios.editar');

        $this->sectorNombre = trim($this->sectorNombre);
        $validated = $this->validate([
            'sectorNombre' => ['required', 'string', 'max:255'],
        ]);

        Sector::query()->findOrFail($this->editingSectorId)->update([
            'nombre' => $validated['sectorNombre'],
        ]);

        $this->cancelSectorEdit();
        $this->dispatch('app-toast', message: 'Sector actualizado correctamente.');
    }

    public function cancelSectorEdit(): void
    {
        $this->reset('editingSectorId', 'sectorNombre');
        $this->resetValidation();
    }

    public function render()
    {
        $sectoresQuery = Sector::query()
            ->where('piso', $this->floorLabel())
            ->with(['activos' => fn ($query) => $query->latest('id')])
            ->withCount('activos')
            ->orderBy('nombre');

        if (! $this->publicMode) {
            $sectoresQuery
                ->with(['insumoExistencias.insumo' => fn ($query) => $query->orderBy('nombre')])
                ->withSum('insumoExistencias', 'cantidad');
        }

        $sectores = $sectoresQuery->get()->keyBy('identificador_mapa');

        $selectedSector = $this->selectedSectorId
            ? $sectores->firstWhere('id', $this->selectedSectorId)
            : null;

        $inventorySearch = trim($this->inventorySearch);
        $mapSearch = trim($this->publicMode ? $this->search : $this->mapSearch);

        $activos = Activo::query()
            ->with('sector')
            ->whereHas('sector', fn ($query) => $query->where('piso', $this->floorLabel()))
            ->when($inventorySearch !== '', function ($query) use ($inventorySearch) {
                $search = "%{$inventorySearch}%";

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

        $mapMatchingSectorIds = $mapSearch === ''
            ? []
            : Activo::query()
                ->whereHas('sector', fn ($query) => $query->where('piso', $this->floorLabel()))
                ->where(function ($query) use ($mapSearch) {
                    $search = "%{$mapSearch}%";

                    $query->where('nombre', 'like', $search)
                        ->orWhere('codigo_inventario', 'like', $search)
                        ->orWhere('numero_serie', 'like', $search)
                        ->when($this->publicMode, fn ($query) => $query->orWhere('estado', 'like', $search))
                        ->orWhereHas('sector', function ($query) use ($search) {
                            $query->where('nombre', 'like', $search)
                                ->orWhere('identificador_mapa', 'like', $search);

                            if ($this->publicMode) {
                                $query->orWhere('piso', 'like', $search);
                            }
                        });
                })
                ->pluck('sector_id')
                ->unique()
                ->all();

        if (! $this->publicMode && $mapSearch !== '') {
            $search = "%{$mapSearch}%";
            $mapMatchingSectorIds = array_values(array_unique([
                ...$mapMatchingSectorIds,
                ...InsumoExistencia::query()
                    ->whereHas('sector', fn ($query) => $query->where('piso', $this->floorLabel()))
                    ->whereHas('insumo', fn ($query) => $query->where('codigo', 'like', $search)->orWhere('nombre', 'like', $search))
                    ->pluck('sector_id')
                    ->all(),
            ]));
        }

        $publicActivos = $this->publicMode
            ? Activo::query()
                ->with('sector')
                ->whereHas('sector', fn ($query) => $query->where('piso', $this->floorLabel()))
                ->when($this->search !== '', function ($query) {
                    $search = "%{$this->search}%";

                    $query->where(function ($query) use ($search) {
                        $query->where('nombre', 'like', $search)
                            ->orWhere('codigo_inventario', 'like', $search)
                            ->orWhere('numero_serie', 'like', $search)
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
            'hotspots' => $this->hotspots($sectores, $mapMatchingSectorIds, $mapSearch !== ''),
            'sectoresSinPosicion' => $sectores->except(array_column($this->hotspotLayout(), 'identificador')),
            'selectedSector' => $selectedSector,
            'selectedHotspot' => $this->selectedHotspot($sectores),
            'totalSectores' => $sectores->count(),
            'totalActivos' => $sectores->sum('activos_count'),
            'totalInsumos' => $this->publicMode ? 0 : $sectores->sum('insumo_existencias_sum_cantidad'),
            'activos' => $activos,
            'publicActivos' => $publicActivos,
            'imagePath' => asset("images/planos/Plano_Piso_{$this->piso}.webp"),
            'availableFloors' => [7, 8, 9],
            'isPublic' => $this->publicMode,
            'mapSearchResultCount' => count($mapMatchingSectorIds),
        ]);
    }

    protected function authorizeBackoffice(): void
    {
        if ($this->publicMode) {
            return;
        }

        Gate::authorize('servicios.ver');
    }

    private function hotspots($sectores, array $matchingSectorIds = [], bool $hasMapSearch = false)
    {
        return collect($this->hotspotLayout())
            ->map(function (array $hotspot) use ($sectores, $matchingSectorIds, $hasMapSearch) {
                $sector = $sectores->get($hotspot['identificador']);

                return [
                    ...$hotspot,
                    'label' => $sector?->nombre ?? $hotspot['label'],
                    'sector' => $sector,
                    'activos_count' => $sector?->activos_count ?? 0,
                    'insumos_count' => $this->publicMode ? 0 : (int) ($sector?->insumo_existencias_sum_cantidad ?? 0),
                    'has_data' => $sector !== null && ($sector->activos_count > 0 || (! $this->publicMode && ($sector->insumo_existencias_sum_cantidad ?? 0) > 0)),
                    'is_map_match' => $hasMapSearch && $sector !== null && in_array($sector->id, $matchingSectorIds, true),
                    'is_map_dimmed' => $hasMapSearch && ($sector === null || ! in_array($sector->id, $matchingSectorIds, true)),
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
        return match ($this->piso) {
            7 => $this->floorHotspots('7_', [
                ['737', 28, 36, 105, 144], ['736', 28, 185, 165, 76], ['735', 28, 260, 165, 70], ['929', 28, 330, 165, 70],
                ['738', 285, 37, 142, 106], ['739', 425, 37, 142, 106], ['712', 310, 210, 217, 190], ['706', 527, 210, 208, 190], ['703', 1010, 212, 305, 192],
                ['713', 140, 460, 77, 116], ['714', 140, 580, 77, 110], ['711', 287, 458, 70, 116], ['710', 358, 458, 70, 116], ['709', 425, 458, 70, 116], ['708', 491, 458, 70, 116], ['707', 561, 458, 80, 116],
                ['715', 287, 572, 70, 116], ['716', 358, 572, 70, 116], ['717', 425, 572, 70, 116], ['718', 491, 572, 70, 116], ['719', 561, 572, 84, 116], ['720', 641, 572, 80, 116],
                ['705', 1047, 458, 70, 116], ['704', 1115, 458, 70, 116], ['702', 1278, 458, 70, 116], ['701', 1343, 458, 60, 116], ['721', 1047, 572, 70, 116], ['722', 1115, 572, 70, 116], ['723', 1278, 572, 120, 116],
                ['733', 30, 744, 99, 171], ['732', 130, 744, 100, 171], ['731', 228, 744, 99, 171], ['730', 320, 744, 99, 171], ['729', 420, 744, 112, 171], ['728', 530, 744, 99, 171], ['727', 630, 744, 105, 171],
                ['ESPACIO_INFERIOR', 735, 744, 280, 171, 'por designar'], ['726', 1015, 744, 135, 171], ['725', 1150, 744, 135, 171], ['724', 1287, 744, 117, 171], ['por designar', 730, 430, 60, 280], ['HALL_CENTRAL', 797, 430, 180, 280, 'HALL CENTRAL'], ['Publico', 985, 430, 60, 280], ['Zona Publica', 640, 41, 760, 120, 'Zona Publica'],
            ]),
            9 => $this->floorHotspots('9_', [
                ['934', 28, 40, 287, 357], ['935', 310, 37, 148, 106], ['936', 460, 37, 142, 106], ['912', 310, 210, 217, 190], ['906', 527, 210, 208, 190], ['PECERA_9', 810, 208, 150, 192, 'Pecera 9'], ['903', 1010, 212, 305, 192],
                ['TERESA', 28, 460, 60, 230, 'Teresa'], ['913', 140, 460, 77, 116], ['914', 140, 580, 77, 110], ['911', 287, 458, 70, 116], ['910', 358, 458, 70, 116], ['909', 425, 458, 70, 116], ['908', 491, 458, 70, 116], ['907', 561, 458, 80, 116], ['DASH_2', 641, 458, 80, 114, '-'],
                ['915', 287, 572, 70, 116], ['916', 358, 572, 70, 116], ['917', 425, 572, 70, 116], ['918', 491, 572, 70, 116], ['919', 561, 572, 84, 116], ['920', 641, 572, 80, 116],
                ['DASH_1', 1047, 458, 70, 116, '-'], ['905', 1115, 458, 70, 116], ['904_OF', 1185, 458, 72, 116, '904'], ['902', 1278, 458, 70, 116], ['901', 1343, 458, 60, 116], ['921', 1047, 572, 70, 116], ['922', 1115, 572, 70, 116], ['923', 1278, 572, 120, 116],
                ['925', 30, 744, 99, 171], ['932', 130, 744, 100, 171], ['931', 228, 744, 99, 171], ['930', 320, 744, 99, 171], ['801', 420, 744, 112, 171], ['928', 530, 744, 99, 171], ['836', 630, 744, 105, 171],
                ['ESPACIO_INFERIOR_1', 735, 744, 140, 171, 'Causas_2'], ['ESPACIO_INFERIOR_2', 875, 744, 140, 171, 'Informaticos'], ['926', 1015, 744, 135, 171], ['933', 1150, 744, 135, 171], ['COMEDOR', 1287, 744, 117, 171], ['Causas_1', 730, 430, 60, 280], ['HALL_CENTRAL', 797, 430, 180, 280, 'HALL CENTRAL'], ['Causas_3', 985, 430, 60, 280], ['Zona Publica', 690, 41, 700, 120, 'Zona Publica'],
            ]),
            default => [
                ['identificador' => '837', 'label' => '837 Erick .A.', 'x' => 31, 'y' => 41, 'w' => 100, 'h' => 140],
                ['identificador' => '836', 'label' => '836 P. Brundl', 'x' => 31, 'y' => 181, 'w' => 159, 'h' => 79],
                ['identificador' => '835', 'label' => '835 M. Garcia', 'x' => 31, 'y' => 260, 'w' => 159, 'h' => 69],
                ['identificador' => '834', 'label' => '929 / 834 Claudia M.', 'x' => 31, 'y' => 329, 'w' => 159, 'h' => 70],
                ['identificador' => '838', 'label' => '838 Cocina', 'x' => 288, 'y' => 41, 'w' => 138, 'h' => 102],
                ['identificador' => '839', 'label' => '839 Banos', 'x' => 426, 'y' => 41, 'w' => 142, 'h' => 102],
                ['identificador' => '812', 'label' => 'SALA 803 812', 'x' => 311, 'y' => 211, 'w' => 217, 'h' => 186],
                ['identificador' => '806', 'label' => 'SALA 802 806', 'x' => 527, 'y' => 211, 'w' => 203, 'h' => 186],
                ['identificador' => 'APUBLICO', 'label' => 'A.PUBLICO', 'x' => 810, 'y' => 211, 'w' => 148, 'h' => 186],
                ['identificador' => '803', 'label' => 'SALA 801', 'x' => 1014, 'y' => 211, 'w' => 300, 'h' => 194],
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
                ['identificador' => 'ADMINISTRACION_HALL', 'label' => 'Adm.', 'x' => 730, 'y' => 430, 'w' => 60, 'h' => 280],
                ['identificador' => 'HALL CENTRAL', 'label' => 'HALL CENTRAL', 'x' => 797, 'y' => 430, 'w' => 180, 'h' => 280],
                ['identificador' => 'SERVICIOS_HALL', 'label' => 'Servicios', 'x' => 985, 'y' => 430, 'w' => 60, 'h' => 280],
                ['identificador' => 'TTyPP', 'label' => 'TTyPP', 'x' => 31, 'y' => 461, 'w' => 60, 'h' => 227],
                ['identificador' => '833', 'label' => '833 Rene Bonnemaison', 'x' => 31, 'y' => 744, 'w' => 99, 'h' => 171],
                ['identificador' => '832', 'label' => '832 Pedro Suarez', 'x' => 130, 'y' => 744, 'w' => 101, 'h' => 171],
                ['identificador' => '831', 'label' => '831 Cristian Soto', 'x' => 231, 'y' => 744, 'w' => 93, 'h' => 171],
                ['identificador' => '830', 'label' => '830 Cristian Fuentealba', 'x' => 324, 'y' => 744, 'w' => 96, 'h' => 171],
                ['identificador' => '829', 'label' => '829', 'x' => 420, 'y' => 744, 'w' => 108, 'h' => 171],
                ['identificador' => '828', 'label' => '828 Sala Deliberac', 'x' => 528, 'y' => 744, 'w' => 101, 'h' => 171],
                ['identificador' => '827', 'label' => '827 Luis Werner', 'x' => 629, 'y' => 744, 'w' => 102, 'h' => 171],
                ['identificador' => 'ADM', 'label' => 'ADM', 'x' => 731, 'y' => 744, 'w' => 60, 'h' => 171],
                ['identificador' => 'ESPACIO_INFERIOR', 'label' => 'Encargados', 'x' => 791, 'y' => 744, 'w' => 224, 'h' => 171],
                ['identificador' => '826', 'label' => '826 Eduardo Fredes', 'x' => 1015, 'y' => 744, 'w' => 136, 'h' => 171],
                ['identificador' => '825', 'label' => '825 Sala Ninos', 'x' => 1151, 'y' => 744, 'w' => 136, 'h' => 171],
                ['identificador' => '823_OF', 'label' => '823 Amelia Munoz', 'x' => 1287, 'y' => 744, 'w' => 113, 'h' => 171],
                ['identificador' => 'Zona Publica', 'label' => 'Zona Publica', 'x' => 640, 'y' => 41, 'w' => 760, 'h' => 120],
                ['identificador' => 'FUERA MAG 835', 'label' => 'FUERA MAG 835', 'x' => 190, 'y' => 329, 'w' => 95, 'h' => 70],
            ],
        };
    }

    private function floorHotspots(string $prefix, array $hotspots): array
    {
        return array_map(fn (array $hotspot) => [
            'identificador' => $prefix.$hotspot[0],
            'label' => $hotspot[5] ?? $hotspot[0],
            'x' => $hotspot[1],
            'y' => $hotspot[2],
            'w' => $hotspot[3],
            'h' => $hotspot[4],
        ], $hotspots);
    }
}
