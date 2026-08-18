<?php

namespace App\Livewire;

use App\Models\AudioSalaEnlace;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class AudiosSalasPage extends Component
{
    private const SALAS = ['701', '801', '802', '803', '901', '902', '903'];

    public string $anio = '';

    public bool $configuracionAbierta = false;

    public array $enlaces = [];

    public function mount(): void
    {
        Gate::authorize('audios_salas.ver');

        $this->anio = (string) now('America/Santiago')->year;
        $this->cargarEnlaces();
    }

    public function updatedAnio(): void
    {
        $this->validateOnly('anio', $this->rules());
        $this->cargarEnlaces();
    }

    public function abrirConfiguracion(): void
    {
        Gate::authorize('audios_salas.gestionar');

        $this->configuracionAbierta = ! $this->configuracionAbierta;
    }

    public function guardar(): void
    {
        Gate::authorize('audios_salas.gestionar');

        $validated = $this->validate();
        $anio = (int) $validated['anio'];

        foreach (self::SALAS as $sala) {
            $url = trim((string) ($validated['enlaces'][$sala] ?? ''));

            if ($url === '') {
                AudioSalaEnlace::query()->where('anio', $anio)->where('sala', $sala)->delete();

                continue;
            }

            $enlace = AudioSalaEnlace::query()->firstOrNew(['anio' => $anio, 'sala' => $sala]);
            $enlace->url = $url;
            $enlace->updated_by = Auth::id();
            $enlace->created_by ??= Auth::id();
            $enlace->save();
        }

        $this->cargarEnlaces();
        $this->dispatch('app-toast', message: 'Enlaces de audio actualizados correctamente.');
    }

    public function render()
    {
        Gate::authorize('audios_salas.ver');

        $anios = AudioSalaEnlace::query()
            ->select('anio')
            ->distinct()
            ->orderByDesc('anio')
            ->pluck('anio')
            ->map(fn ($anio) => (string) $anio)
            ->prepend((string) now('America/Santiago')->year)
            ->unique()
            ->values();

        return view('livewire.audios-salas-page', [
            'anios' => $anios,
            'salas' => self::SALAS,
        ]);
    }

    private function cargarEnlaces(): void
    {
        $existentes = AudioSalaEnlace::query()
            ->where('anio', (int) $this->anio)
            ->pluck('url', 'sala')
            ->all();

        $this->enlaces = collect(self::SALAS)
            ->mapWithKeys(fn (string $sala) => [$sala => $existentes[$sala] ?? ''])
            ->all();
    }

    protected function rules(): array
    {
        return [
            'anio' => ['required', 'integer', 'between:2000,'.(now('America/Santiago')->year + 1)],
            'enlaces' => ['required', 'array'],
            'enlaces.*' => ['nullable', 'string', 'max:2048', 'url:http,https'],
        ];
    }
}
