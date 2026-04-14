<?php

namespace App\Livewire;

use App\Models\Funcionario;
use App\Services\FuncionarioCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FuncionariosPage extends Component
{
    public string $nombre = '';

    public string $rol = '';

    public string $email = '';

    public string $fechaCumpleanos = '';

    public function createFuncionario(): void
    {
        Gate::authorize('navbar.funcionarios.editable');

        $data = $this->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'rol' => ['required', Rule::in(app(FuncionarioCatalog::class)->roles())],
            'email' => ['required', 'email', 'max:255', 'unique:funcionarios,email'],
            'fechaCumpleanos' => ['required', 'date'],
        ], [], [
            'fechaCumpleanos' => 'fecha_cumpleanos',
        ]);

        Funcionario::create([
            'nombre' => $data['nombre'],
            'rol' => $data['rol'],
            'email' => $data['email'],
            'fecha_cumpleanos' => $data['fechaCumpleanos'],
        ]);

        $this->reset(['nombre', 'rol', 'email', 'fechaCumpleanos']);
        session()->flash('success', 'Funcionario agregado correctamente.');
    }

    public function deleteFuncionario(int $id): void
    {
        Gate::authorize('navbar.funcionarios.editable');

        $funcionario = Funcionario::query()->findOrFail($id);
        $funcionario->delete();

        session()->flash('success', 'Funcionario eliminado correctamente.');
    }

    public function render()
    {
        Gate::authorize('navbar.funcionarios.visible');

        $catalog = app(FuncionarioCatalog::class);

        return view('livewire.funcionarios-page', [
            'solicitantes' => Funcionario::query()->orderBy('nombre')->get(),
            'roles' => $catalog->roleOptions(),
        ]);
    }
}
