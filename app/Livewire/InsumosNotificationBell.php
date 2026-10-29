<?php

namespace App\Livewire;

use App\Notifications\InsumosServiciosNotification;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class InsumosNotificationBell extends Component
{
    #[Computed]
    public function unread(): int
    {
        return auth()->user()?->unreadNotifications()->where('type', InsumosServiciosNotification::class)->count() ?? 0;
    }

    #[On('notificaciones-actualizadas')]
    public function refrescar(): void
    {
        unset($this->unread);
    }

    public function render()
    {
        return view('livewire.insumos-notification-bell');
    }
}
