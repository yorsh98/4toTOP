<?php

namespace App\Livewire\Security;

use App\Models\SecurityAudit;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AuditPage extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $q = '';

    #[Url(as: 'action', except: '')]
    public string $action = '';

    #[Url(as: 'entity', except: '')]
    public string $entity = '';

    public function updatingQ(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingEntity(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        Gate::authorize('navbar.seguridad.visible');

        $q = addcslashes(mb_substr($this->q, 0, 100), '\\%_');
        $actions = SecurityAudit::query()->select('action')->distinct()->orderBy('action')->pluck('action');
        $entities = SecurityAudit::query()->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type');
        $action = $actions->contains($this->action) ? $this->action : '';
        $entity = $entities->contains($this->entity) ? $this->entity : '';

        $audits = SecurityAudit::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('actor_name', 'like', "%{$q}%")
                        ->orWhere('actor_email', 'like', "%{$q}%")
                        ->orWhere('entity_id', 'like', "%{$q}%")
                        ->orWhere('action', 'like', "%{$q}%");
                });
            })
            ->when($action !== '', fn ($query) => $query->where('action', $action))
            ->when($entity !== '', fn ($query) => $query->where('entity_type', $entity))
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.security.audit-page', [
            'audits' => $audits,
            'actions' => $actions,
            'entities' => $entities,
        ]);
    }
}
