<?php

namespace App\Livewire;

use App\Models\Insumo;
use App\Models\InsumoSolicitud;
use App\Services\InsumoSolicitudService;
use DomainException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InsumosSolicitudesPage extends Component
{
    use WithPagination;

    public string $search = '';

    /** @var array<int, int> */
    public array $cart = [];

    public string $observacion = '';

    public string $firma = '';

    public function mount(): void
    {
        Gate::authorize('insumos.solicitar');
        $this->firma = auth()->user()->name;
    }

    public function updatedSearch(): void
    {
        $this->resetPage('catalogoPage');
    }

    public function agregar(int $insumoId): void
    {
        Gate::authorize('insumos.solicitar');

        if (! Insumo::query()->whereKey($insumoId)->where('activo', true)->exists()) {
            $this->addError('cart', 'El insumo ya no esta disponible.');

            return;
        }

        $this->cart[$insumoId] = $this->cart[$insumoId] ?? 1;
    }

    public function quitar(int $insumoId): void
    {
        Gate::authorize('insumos.solicitar');

        unset($this->cart[$insumoId]);
    }

    public function solicitar(InsumoSolicitudService $solicitudService): void
    {
        Gate::authorize('insumos.solicitar');

        $this->validate(['observacion' => ['nullable', 'string', 'max:2000']]);

        if (Validator::make(['cart' => $this->cart], [
            'cart' => ['array'],
            'cart.*' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ])->fails()) {
            $this->addError('cart', 'La solicitud contiene cantidades invalidas.');

            return;
        }

        $items = collect($this->cart)
            ->map(fn ($cantidad, $insumoId): array => ['insumo_id' => $insumoId, 'cantidad' => $cantidad])
            ->values()
            ->all();

        if (Validator::make(['items' => $items], [
            'items.*.insumo_id' => ['required', 'integer', Rule::exists('insumos', 'id')->where('activo', true)],
        ])->fails()) {
            $this->addError('cart', 'El insumo ya no esta disponible.');

            return;
        }

        $items = array_map(fn (array $item): array => [
            'insumo_id' => (int) $item['insumo_id'],
            'cantidad' => (int) $item['cantidad'],
        ], $items);

        try {
            $solicitudService->crear($items, auth()->id(), filled($this->observacion) ? $this->observacion : null, $this->firma);
        } catch (DomainException|InvalidArgumentException $exception) {
            $this->addError('cart', $exception->getMessage());

            return;
        }

        $this->reset('cart', 'observacion');
        $this->firma = auth()->user()->name;
        $this->dispatch('app-toast', icon: 'success', message: 'Solicitud enviada a Servicios.');
    }

    public function cancelar(int $solicitudId, InsumoSolicitudService $solicitudService): void
    {
        Gate::authorize('insumos.solicitar');

        $solicitud = InsumoSolicitud::query()->findOrFail($solicitudId);

        try {
            $solicitudService->cancelar($solicitud, auth()->id());
        } catch (DomainException $exception) {
            $this->dispatch('app-toast', icon: 'error', message: $exception->getMessage());
        }
    }

    public function render()
    {
        Gate::authorize('insumos.solicitar');

        $search = mb_substr(trim($this->search), 0, 255);
        $catalogo = Insumo::query()
            ->where('activo', true)
            ->withSum('existencias', 'cantidad')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('nombre', 'like', "%{$search}%");
            })
            ->orderBy('nombre')
            ->paginate(10, pageName: 'catalogoPage');

        $solicitudes = InsumoSolicitud::query()
            ->where('solicitante_user_id', auth()->id())
            ->with(['detalles.insumo', 'detalles.movimientos'])
            ->latest('id')
            ->paginate(10, pageName: 'solicitudesPage');

        return view('livewire.insumos-solicitudes-page', [
            'catalogo' => $catalogo,
            'cartInsumos' => Insumo::query()->whereIn('id', array_keys($this->cart))->orderBy('nombre')->get(),
            'solicitudes' => $solicitudes,
            'firmas' => array_merge([auth()->user()->name], array_map(fn (int $sala): string => "Sala {$sala}", [701, 801, 802, 803, 901, 902, 903])),
        ]);
    }
}
