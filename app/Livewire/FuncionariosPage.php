<?php

namespace App\Livewire;

use App\Models\Funcionario;
use App\Services\FuncionarioCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FuncionariosPage extends Component
{
    use WithPagination;

    protected $paginationTheme = 'bootstrap';

    public ?int $editingId = null;

    public string $search = '';

    public string $nombre = '';

    public string $run = '';

    public string $rol = '';

    public string $email = '';

    public string $telefono = '';

    public string $fechaCumpleanos = '';

    public function createFuncionario(): void
    {
        Gate::authorize('navbar.funcionarios.editable');

        $data = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'run' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', Rule::in(app(FuncionarioCatalog::class)->roles())],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('funcionarios', 'email')->whereNull('deleted_at'),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fechaCumpleanos' => ['required', 'date'],
        ], [], [
            'fechaCumpleanos' => 'fecha_cumpleanos',
        ]);

        $legacyDeleted = Funcionario::withTrashed()
            ->where('email', $data['email'])
            ->whereNotNull('deleted_at')
            ->first();

        if ($legacyDeleted) {
            $legacyDeleted->restore();
            $legacyDeleted->update([
                'nombre' => $data['nombre'],
                'run' => $data['run'] ?? null,
                'rol' => $data['rol'],
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'fecha_cumpleanos' => $data['fechaCumpleanos'],
            ]);

            $this->resetForm();
            $this->dispatch('app-toast', message: 'Funcionario restaurado correctamente.');

            return;
        }

        Funcionario::create([
            'nombre' => $data['nombre'],
            'run' => $data['run'] ?? null,
            'rol' => $data['rol'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'fecha_cumpleanos' => $data['fechaCumpleanos'],
        ]);

        $this->resetForm();
        $this->dispatch('app-toast', message: 'Funcionario agregado correctamente.');
    }

    public function editFuncionario(int $id): void
    {
        Gate::authorize('navbar.funcionarios.editable');

        $funcionario = Funcionario::query()->findOrFail($id);

        $this->editingId = $funcionario->id;
        $this->nombre = $funcionario->nombre;
        $this->run = $funcionario->run ?? '';
        $this->rol = $funcionario->rol;
        $this->email = $funcionario->email;
        $this->telefono = $funcionario->telefono ?? '';
        $this->fechaCumpleanos = optional($funcionario->fecha_cumpleanos)->format('Y-m-d') ?? '';
    }

    public function updateFuncionario(): void
    {
        Gate::authorize('navbar.funcionarios.editable');

        if (! $this->editingId) {
            return;
        }

        $funcionario = Funcionario::query()->findOrFail($this->editingId);

        $data = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'run' => ['nullable', 'string', 'max:20'],
            'rol' => ['required', Rule::in(app(FuncionarioCatalog::class)->roles())],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('funcionarios', 'email')
                    ->whereNull('deleted_at')
                    ->ignore($funcionario->id),
            ],
            'telefono' => ['nullable', 'string', 'max:30'],
            'fechaCumpleanos' => ['required', 'date'],
        ], [], [
            'fechaCumpleanos' => 'fecha_cumpleanos',
        ]);

        $funcionario->update([
            'nombre' => $data['nombre'],
            'run' => $data['run'] ?? null,
            'rol' => $data['rol'],
            'email' => $data['email'],
            'telefono' => $data['telefono'] ?? null,
            'fecha_cumpleanos' => $data['fechaCumpleanos'],
        ]);

        $this->resetForm();
        $this->dispatch('app-toast', message: 'Funcionario actualizado correctamente.');
    }

    public function resetForm(): void
    {
        $this->reset(['editingId', 'nombre', 'run', 'rol', 'email', 'telefono', 'fechaCumpleanos']);
        $this->resetValidation();
    }

    public function deleteFuncionario(int $id): void
    {
        Gate::authorize('navbar.funcionarios.editable');

        $funcionario = Funcionario::query()->findOrFail($id);

        $funcionario->email = $this->buildDeletedEmail($funcionario->email, $funcionario->id);
        $funcionario->save();
        $funcionario->delete();

        if ($this->editingId === $id) {
            $this->resetForm();
        }

        $this->dispatch('app-toast', message: 'Funcionario eliminado correctamente.');
    }

    private function buildDeletedEmail(string $email, int $id): string
    {
        $token = '__deleted_'.$id.'_'.now()->format('YmdHis').'_'.Str::lower(Str::random(4));

        if (! str_contains($email, '@')) {
            return Str::limit($email, 220, '').$token;
        }

        [$local, $domain] = explode('@', $email, 2);
        $maxLocalLength = max(1, 64 - strlen($token));
        $safeLocal = Str::limit($local, $maxLocalLength, '');

        return $safeLocal.$token.'@'.$domain;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('navbar.funcionarios.visible');

        $catalog = app(FuncionarioCatalog::class);

        $term = trim($this->search);
        $matchingRoleKeys = [];

        if ($term !== '') {
            $normalizedTerm = mb_strtolower($term);

            foreach ($catalog->roleOptions() as $key => $label) {
                if (str_contains(mb_strtolower((string) $label), $normalizedTerm)) {
                    $matchingRoleKeys[] = (string) $key;
                }
            }
        }

        $solicitantes = Funcionario::query()
            ->when($term !== '', function ($query) use ($term, $matchingRoleKeys) {
                $query->where(function ($subQuery) use ($term, $matchingRoleKeys) {
                    $subQuery
                        ->where('nombre', 'like', "%{$term}%")
                        ->orWhere('run', 'like', "%{$term}%")
                        ->orWhere('rol', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('telefono', 'like', "%{$term}%");

                    if ($matchingRoleKeys !== []) {
                        $subQuery->orWhereIn('rol', $matchingRoleKeys);
                    }
                });
            })
            ->orderBy('nombre')
            ->paginate(20);

        return view('livewire.funcionarios-page', [
            'solicitantes' => $solicitantes,
            'roles' => $catalog->roleOptions(),
        ]);
    }
}
