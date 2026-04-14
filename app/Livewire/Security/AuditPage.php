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

        $audits = SecurityAudit::query()
            ->when($this->q !== '', function ($query) {
                $query->where(function ($sub) {
                    $sub->where('actor_name', 'like', "%{$this->q}%")
                        ->orWhere('actor_email', 'like', "%{$this->q}%")
                        ->orWhere('entity_id', 'like', "%{$this->q}%")
                        ->orWhere('action', 'like', "%{$this->q}%");
                });
            })
            ->when($this->action !== '', fn ($query) => $query->where('action', $this->action))
            ->when($this->entity !== '', fn ($query) => $query->where('entity_type', $this->entity))
            ->orderByDesc('id')
            ->paginate(25);

        return view('livewire.security.audit-page', [
            'audits' => $audits,
            'actions' => SecurityAudit::query()->select('action')->distinct()->orderBy('action')->pluck('action'),
            'entities' => SecurityAudit::query()->select('entity_type')->distinct()->orderBy('entity_type')->pluck('entity_type'),
        ]);
    }
}
