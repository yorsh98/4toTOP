<?php

namespace App\Livewire;

use App\Notifications\InsumosServiciosNotification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InsumosNotificacionesPage extends Component
{
    use WithPagination;

    public function marcarLeida(string $notificationId): void
    {
        auth()->user()
            ->notifications()
            ->where('type', InsumosServiciosNotification::class)
            ->whereKey($notificationId)
            ->firstOrFail()
            ->markAsRead();

        $this->dispatch('notificaciones-actualizadas');
    }

    public function marcarTodasLeidas(): void
    {
        auth()->user()
            ->unreadNotifications()
            ->where('type', InsumosServiciosNotification::class)
            ->update(['read_at' => now()]);

        $this->dispatch('notificaciones-actualizadas');
    }

    public function render()
    {
        return view('livewire.insumos-notificaciones-page', [
            'notifications' => auth()->user()
                ->notifications()
                ->where('type', InsumosServiciosNotification::class)
                ->latest()
                ->paginate(15),
        ]);
    }
}
