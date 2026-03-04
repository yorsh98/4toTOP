<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\buscadorsentencias;

class BuscadorSentenciasTabla extends Component
{
    use WithPagination;

    public string $search = '';
    public string $filtroInstancia = '';
    public int $perPage = 10;

    public string $sortField = 'fecha_decision';
    public string $sortDirection = 'desc';

    protected $paginationTheme = 'bootstrap';
    // Para mantener estado en la URL (opcional pero útil)
    protected $queryString = [
        'search' => ['except' => ''],
        'filtroInstancia' => ['except' => ''],
        'perPage' => ['except' => 10],
        'sortField' => ['except' => 'fecha_decision'],
        'sortDirection' => ['except' => 'desc'],
    ];

    // Cada vez que cambie search/filtro/perPage, vuelve a página 1
    public function updatedSearch() { $this->resetPage(); }
    public function updatedFiltroInstancia() { $this->resetPage(); }
    public function updatedPerPage() { $this->resetPage(); }

    public function sortBy(string $field): void
    {
        $allowed = [
            'ruc','rit','ano','nombre_partes','materia','fecha_decision','glosa_decision','juez','instancia'
        ];

        if (!in_array($field, $allowed, true)) return;

        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }

        $this->resetPage();
    }

    public function render()
    {
        $instancias = buscadorsentencias::query()
            ->whereNotNull('instancia')
            ->where('instancia', '<>', '')
            ->distinct()
            ->orderBy('instancia')
            ->pluck('instancia')
            ->values();

        $q = buscadorsentencias::query();

        if ($this->filtroInstancia !== '') {
            $q->where('instancia', $this->filtroInstancia);
        }

        if ($this->search !== '') {
            $raw = trim($this->search);

            // Normalizamos separadores comunes: "136-2025", "136/2025", "136 2025", "136—2025"
            $normalized = preg_replace('/[\/\s—–]+/u', '-', $raw);

            // Detecta "RIT-AÑO" exacto (1-6 dígitos)-(4 dígitos)
            if (preg_match('/^(\d{1,6})-(\d{4})$/', $normalized, $m)) {
                $rit = (int) $m[1];
                $ano = (int) $m[2];

                $q->where('rit', $rit)->where('ano', $ano);
            } else {
                $s = $raw;

                $q->where(function ($sub) use ($s) {
                    $sub->where('ruc', 'like', "%{$s}%")
                        ->orWhere('rit', 'like', "%{$s}%")
                        ->orWhere('ano', 'like', "%{$s}%")
                        ->orWhere('nombre_partes', 'like', "%{$s}%")
                        ->orWhere('materia', 'like', "%{$s}%")
                        ->orWhere('glosa_decision', 'like', "%{$s}%")
                        ->orWhere('juez', 'like', "%{$s}%")
                        ->orWhere('instancia', 'like', "%{$s}%");
                });
            }
        }

        $sentencias = $q
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.buscador-sentencias-tabla', [
            'sentencias' => $sentencias,
            'instancias' => $instancias,
        ]);
    }
}