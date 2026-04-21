<?php

namespace App\Livewire;

use App\Models\Audiencia;
use App\Models\BitacoraPruebaDia;
use App\Models\BitacoraPruebaItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BitacoraPruebasPage extends Component
{
    public bool $showViewer = false;

    public string $rit = '';

    public string $enlaceActa = '';

    public string $fechaDia = '';

    public int $numeroDia = 1;

    /** @var array<int, array{nombre_prueba: string, institucion_exhibe: string}> */
    public array $newItems = [];

    public function mount(): void
    {
        Gate::authorize('bitacora_pruebas.ver');
    }

    protected function rules(): array
    {
        return [
            'rit' => ['required', 'string', 'max:255'],
            'enlaceActa' => ['nullable', 'url', 'max:2048'],
            'numeroDia' => ['required', 'integer', 'min:1'],
            'fechaDia' => ['required', 'date'],
        ];
    }

    public function updatedRit(): void
    {
        $this->enlaceActa = (string) (BitacoraPruebaDia::query()
            ->where('rit', trim($this->rit))
            ->whereNotNull('enlace_acta')
            ->where('enlace_acta', '!=', '')
            ->value('enlace_acta') ?? '');
    }

    public function createDia(): void
    {
        Gate::authorize('bitacora_pruebas.crear');

        $data = $this->validate();

        $exists = BitacoraPruebaDia::query()
            ->where('rit', $data['rit'])
            ->where('numero_dia', $data['numeroDia'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'numeroDia' => 'Ya existe ese dia para el RIT seleccionado.',
            ]);
        }

        $rit = trim($data['rit']);
        $enlaceActa = trim($data['enlaceActa'] ?? '');

        if ($enlaceActa === '') {
            $enlaceActa = (string) (BitacoraPruebaDia::query()
                ->where('rit', $rit)
                ->whereNotNull('enlace_acta')
                ->where('enlace_acta', '!=', '')
                ->value('enlace_acta') ?? '');
        } else {
            BitacoraPruebaDia::query()
                ->where('rit', $rit)
                ->update(['enlace_acta' => $enlaceActa]);
        }

        BitacoraPruebaDia::query()->create([
            'rit' => $rit,
            'enlace_acta' => $enlaceActa !== '' ? $enlaceActa : null,
            'numero_dia' => $data['numeroDia'],
            'fecha_dia' => $data['fechaDia'],
        ]);

        $this->reset(['fechaDia']);
        $this->numeroDia = 1;
        $this->enlaceActa = $enlaceActa;
        session()->flash('ok', 'Dia agregado correctamente.');
    }

    public function addItem(int $diaId): void
    {
        Gate::authorize('bitacora_pruebas.editar');

        $dia = BitacoraPruebaDia::query()->findOrFail($diaId);

        $payload = $this->newItems[$diaId] ?? ['nombre_prueba' => '', 'institucion_exhibe' => ''];

        $validated = validator($payload, [
            'nombre_prueba' => ['required', 'string', 'max:255'],
            'institucion_exhibe' => ['required', 'string', 'max:255'],
        ], [], [
            'nombre_prueba' => 'nombre de la prueba',
            'institucion_exhibe' => 'institucion que exhibe',
        ])->validate();

        BitacoraPruebaItem::query()->create([
            'bitacora_pruebas_dia_id' => $dia->id,
            'nombre_prueba' => trim($validated['nombre_prueba']),
            'institucion_exhibe' => trim($validated['institucion_exhibe']),
            'ingresado_por_user_id' => Auth::id(),
        ]);

        $this->newItems[$diaId] = ['nombre_prueba' => '', 'institucion_exhibe' => ''];
        session()->flash('ok', 'Prueba agregada correctamente.');
    }

    public function deleteDia(int $diaId): void
    {
        Gate::authorize('bitacora_pruebas.editar');

        $dia = BitacoraPruebaDia::query()->with('items')->findOrFail($diaId);
        $dia->items()->delete();
        $dia->delete();

        session()->flash('ok', 'Dia eliminado correctamente.');
    }

    public function deleteItem(int $itemId): void
    {
        Gate::authorize('bitacora_pruebas.editar');

        $item = BitacoraPruebaItem::query()->findOrFail($itemId);
        $item->delete();

        session()->flash('ok', 'Prueba eliminada correctamente.');
    }

    public function toggleViewer(): void
    {
        Gate::authorize('bitacora_pruebas.ver');

        $this->showViewer = ! $this->showViewer;
    }

    public function render()
    {
        Gate::authorize('bitacora_pruebas.ver');

        $ritsAudiencias = Audiencia::query()->distinct()->orderBy('rit')->pluck('rit');
        $ritsBitacora = BitacoraPruebaDia::query()->distinct()->orderBy('rit')->pluck('rit');

        $rits = $ritsAudiencias
            ->merge($ritsBitacora)
            ->filter(fn ($rit) => is_string($rit) && trim($rit) !== '')
            ->unique()
            ->sort()
            ->values();

        $ritsSugeridosUsuario = collect();

        if (Auth::check()) {
            $ritsSugeridosUsuario = BitacoraPruebaItem::query()
                ->where('ingresado_por_user_id', Auth::id())
                ->whereHas('dia')
                ->with('dia:id,rit')
                ->get()
                ->map(fn (BitacoraPruebaItem $item) => $item->dia?->rit)
                ->filter(fn ($rit) => is_string($rit) && trim($rit) !== '')
                ->unique()
                ->sort()
                ->values();
        }

        if ($ritsSugeridosUsuario->isEmpty()) {
            $ritsSugeridosUsuario = $rits;
        }

        $dias = collect();

        if ($this->rit !== '') {
            $dias = BitacoraPruebaDia::query()
                ->where('rit', $this->rit)
                ->with(['items' => fn ($query) => $query->with('usuario')->orderBy('created_at')])
                ->orderBy('numero_dia')
                ->get();
        }

        return view('livewire.bitacora-pruebas-page', [
            'rits' => $rits,
            'ritsSugeridosUsuario' => $ritsSugeridosUsuario,
            'dias' => $dias,
        ]);
    }
}
