<?php

namespace App\Livewire\Security;

use App\Livewire\Security\Concerns\LogsSecurityAudit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
class RolesPage extends Component
{
    use LogsSecurityAudit;

    public string $name = '';

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:roles,name'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.regex' => 'El nombre del rol solo admite letras, numeros, punto, guion y guion bajo.',
        ];
    }

    public function createRole(): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $data = $this->validate();

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $this->logSecurityAudit(
            action: 'role.created',
            entityType: 'role',
            entityId: (string) $role->id,
            before: null,
            after: [
                'name' => $role->name,
                'guard_name' => $role->guard_name,
            ],
        );

        $this->reset('name');
        $this->dispatch('app-toast', message: 'Rol creado correctamente.');
    }

    public function deleteRole(int $roleId): void
    {
        Gate::authorize('navbar.seguridad.editable');

        $role = Role::query()->findOrFail($roleId);

        if ($role->name === 'SuperAdmin') {
            session()->flash('error', 'No se puede eliminar el rol SuperAdmin.');

            return;
        }

        if ($role->users()->exists()) {
            session()->flash('error', 'No se puede eliminar un rol asignado a usuarios.');

            return;
        }

        $before = [
            'name' => $role->name,
            'guard_name' => $role->guard_name,
            'users_count' => $role->users()->count(),
            'permissions' => $role->permissions()->pluck('name')->values()->all(),
        ];

        $role->delete();

        $this->logSecurityAudit(
            action: 'role.deleted',
            entityType: 'role',
            entityId: (string) $roleId,
            before: $before,
            after: null,
        );

        $this->dispatch('app-toast', message: 'Rol eliminado correctamente.');
    }

    public function render()
    {
        Gate::authorize('navbar.seguridad.visible');

        return view('livewire.security.roles-page', [
            'roles' => Role::query()
                ->withCount('users')
                ->orderBy('name')
                ->get(),
        ]);
    }
}
