<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

@php
    $unreadInsumosNotifications = auth()->user()
        ->unreadNotifications()
        ->where('type', \App\Notifications\InsumosServiciosNotification::class)
        ->count();

    $menuItems = array_merge([[
            'label' => __('Inicio'),
            'route' => 'dashboard',
            'active' => 'dashboard',
            'icon' => 'fa-solid fa-house',
            'permissions' => ['navbar.inicio.visible', 'navbar.inicio.editable'],
            'accent' => '#38bdf8',
        ]], app(\App\Services\BackofficeModules::class)->items());
@endphp

<nav x-data="{
    open: false,
    collapsed: sessionStorage.getItem('boSidebarCollapsed') === 'true',
    theme: document.documentElement.dataset.theme,
    toggleCollapsed() {
        const apply = async () => { this.collapsed = !this.collapsed; sessionStorage.setItem('boSidebarCollapsed', this.collapsed); await this.$nextTick(); };
        window.boSidebar ? window.boSidebar.toggle(this.$root, apply) : apply();
    },
    toggleTheme(event) {
        this.theme = this.theme === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = this.theme; try { localStorage.setItem('fo-theme', this.theme); } catch (_) {}
        const icon = event?.currentTarget?.querySelector('i');
        this.$nextTick(() => window.boSidebar?.spin(icon));
    }
}" :class="collapsed ? 'lg:w-20' : 'lg:w-72'" class="bo-nav w-full lg:shrink-0">
    <div class="bo-nav-mobile flex h-16 items-center justify-between px-4 lg:hidden">
        <a href="{{ route('dashboard') }}" wire:navigate class="bo-brand"><img src="{{ asset('img/pjud_blanco.webp') }}" alt="Poder Judicial de Chile"><span>4toTOP</span></a>
        <button type="button" class="bo-nav-toggle" @click="open = !open; $nextTick(() => window.boSidebar?.spin($el.querySelector('i')))" :aria-expanded="open" :aria-label="open ? 'Cerrar menú' : 'Abrir menú'"><i class="fa-solid" :class="open ? 'fa-xmark' : 'fa-bars'"></i></button>
    </div>
    <aside class="bo-sidebar hidden h-screen lg:sticky lg:top-0 lg:flex lg:flex-col">
        <div class="bo-nav-head flex h-16 items-center justify-between px-3">
            <a href="{{ route('dashboard') }}" wire:navigate class="bo-brand" x-show="!collapsed"><img src="{{ asset('img/pjud_blanco.webp') }}" alt="Poder Judicial de Chile"><span>4toTOP</span></a>
            <button type="button" class="bo-nav-toggle" @click="toggleCollapsed" :title="collapsed ? 'Expandir menú' : 'Colapsar menú'" :aria-label="collapsed ? 'Expandir menú' : 'Colapsar menú'"><i class="fa-solid" :class="collapsed ? 'fa-angles-right' : 'fa-angles-left'"></i></button>
        </div>
        <div class="flex-1 overflow-y-auto p-3">
            @foreach ($menuItems as $item)
                @canany($item['permissions'])
                    <a href="{{ route($item['route']) }}" wire:navigate title="{{ $item['label'] }}" style="--bo-accent: {{ $item['accent'] }}; --cm-i: {{ $loop->index }}" class="bo-nav-link cm-press {{ request()->routeIs($item['active']) ? 'is-active' : '' }}" :class="collapsed ? 'justify-center' : ''"><i class="{{ $item['icon'] }}" aria-hidden="true"></i><span x-show="!collapsed">{{ $item['label'] }}</span></a>
                @endcanany
            @endforeach
        </div>
        <div class="bo-nav-footer p-3">
            <div x-show="!collapsed" class="bo-nav-user" x-data="{{ json_encode(['name' => auth()->user()->name]) }}"><strong x-text="name" x-on:profile-updated.window="name = $event.detail.name"></strong><span>{{ auth()->user()->email }}</span></div>
            <a href="{{ route('insumos.notificaciones') }}" wire:navigate class="bo-nav-link" :class="collapsed ? 'justify-center' : ''" title="Notificaciones"><i class="fa-solid fa-bell"></i><span x-show="!collapsed">Notificaciones</span>@if ($unreadInsumosNotifications)<span class="bo-badge">{{ $unreadInsumosNotifications }}</span>@endif</a>
            <a href="{{ route('profile') }}" wire:navigate class="bo-nav-link" :class="collapsed ? 'justify-center' : ''" title="Perfil"><i class="fa-solid fa-user"></i><span x-show="!collapsed">Perfil</span></a>
            <a href="{{ route('welcome') }}" class="bo-nav-link" :class="collapsed ? 'justify-center' : ''" title="Ver sitio público"><i class="fa-solid fa-arrow-up-right-from-square"></i><span x-show="!collapsed">Ver sitio público</span></a>
            <button type="button" @click="toggleTheme($event)" class="bo-nav-link w-full" :class="collapsed ? 'justify-center' : ''" title="Cambiar tema" aria-label="Cambiar tema"><i class="fa-solid" :class="theme === 'dark' ? 'fa-sun' : 'fa-moon'"></i><span x-show="!collapsed">Cambiar tema</span></button>
            <button type="button" wire:click="logout" class="bo-nav-link w-full" :class="collapsed ? 'justify-center' : ''" title="Salir"><i class="fa-solid fa-right-from-bracket"></i><span x-show="!collapsed">Salir</span></button>
        </div>
    </aside>
    <div x-show="open" x-cloak class="bo-sidebar p-3 lg:hidden"
         x-transition:enter="bo-menu-t" x-transition:enter-start="bo-menu-from" x-transition:enter-end=""
         x-transition:leave="bo-menu-t" x-transition:leave-start="" x-transition:leave-end="bo-menu-from">
        @foreach ($menuItems as $item)
            @canany($item['permissions'])
                <a href="{{ route($item['route']) }}" wire:navigate style="--bo-accent: {{ $item['accent'] }}" class="bo-nav-link {{ request()->routeIs($item['active']) ? 'is-active' : '' }}"><i class="{{ $item['icon'] }}"></i><span>{{ $item['label'] }}</span></a>
            @endcanany
        @endforeach
        <div class="bo-nav-footer pt-3">
            <a href="{{ route('insumos.notificaciones') }}" wire:navigate class="bo-nav-link"><i class="fa-solid fa-bell"></i>Notificaciones @if ($unreadInsumosNotifications)<span class="bo-badge">{{ $unreadInsumosNotifications }}</span>@endif</a>
            <a href="{{ route('profile') }}" wire:navigate class="bo-nav-link"><i class="fa-solid fa-user"></i>Perfil</a>
            <a href="{{ route('welcome') }}" class="bo-nav-link"><i class="fa-solid fa-arrow-up-right-from-square"></i>Ver sitio público</a>
            <button type="button" @click="toggleTheme($event)" class="bo-nav-link w-full" aria-label="Cambiar tema"><i class="fa-solid" :class="theme === 'dark' ? 'fa-sun' : 'fa-moon'"></i>Cambiar tema</button>
            <button type="button" wire:click="logout" class="bo-nav-link w-full"><i class="fa-solid fa-right-from-bracket"></i>Salir</button>
        </div>
    </div>
</nav>
