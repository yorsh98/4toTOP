<div wire:poll.15s.visible>
    @auth
        <a href="{{ route('insumos.notificaciones') }}" wire:navigate class="bo-header-btn cm-press" title="Notificaciones" aria-label="Notificaciones{{ $this->unread ? ', '.$this->unread.' sin leer' : '' }}">
            <i class="fa-solid fa-bell"></i>
            @if ($this->unread > 0)<span class="bo-badge">{{ $this->unread > 99 ? '99+' : $this->unread }}</span>@endif
        </a>
    @endauth
</div>
