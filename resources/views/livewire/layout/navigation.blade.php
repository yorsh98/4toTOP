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
    $menuItems = [
        [
            'label' => __('Inicio'),
            'route' => 'dashboard',
            'active' => 'dashboard',
            'icon' => 'fa-solid fa-house',
            'permissions' => ['navbar.inicio.visible', 'navbar.inicio.editable'],
        ],
        [
            'label' => __('Funcionarios'),
            'route' => 'addfuncionario.index',
            'active' => 'addfuncionario*',
            'icon' => 'fa-solid fa-users',
            'permissions' => ['navbar.funcionarios.visible', 'navbar.funcionarios.editable'],
        ],
        [
            'label' => __('Oficios / Libertades'),
            'route' => 'oficiolibertad',
            'active' => 'oficiolibertad',
            'icon' => 'fa-solid fa-file-signature',
            'permissions' => ['navbar.oficios_libertades.visible', 'navbar.oficios_libertades.editable'],
        ],
        [
            'label' => __('Turnos'),
            'route' => 'turno',
            'active' => 'turno',
            'icon' => 'fa-solid fa-calendar-days',
            'permissions' => ['navbar.turnos.visible', 'navbar.turnos.editable'],
        ],
        [
            'label' => __('Guias'),
            'route' => 'guias',
            'active' => 'guias',
            'icon' => 'fa-solid fa-book',
            'permissions' => ['navbar.guias.visible', 'navbar.guias.editable'],
        ],
        [
            'label' => __('Audiencias'),
            'route' => 'AdmAudv',
            'active' => 'AdmAudv',
            'icon' => 'fa-solid fa-gavel',
            'permissions' => ['navbar.audiencias.visible', 'navbar.audiencias.editable'],
        ],
        [
            'label' => 'TTYPP',
            'route' => 'ttypp.index',
            'active' => 'ttypp.*',
            'icon' => 'fa-solid fa-user-shield',
            'permissions' => ['navbar.ttypp.visible', 'navbar.ttypp.editable'],
        ],
        [
            'label' => __('Bitacora Pruebas'),
            'route' => 'bitacora-pruebas.index',
            'active' => 'bitacora-pruebas.*',
            'icon' => 'fa-solid fa-clipboard-list',
            'permissions' => ['navbar.bitacora_pruebas.visible', 'navbar.bitacora_pruebas.editable'],
        ],
        [
            'label' => __('Ausencias'),
            'route' => 'ausentismo',
            'active' => 'ausentismo',
            'icon' => 'fa-solid fa-user-clock',
            'permissions' => ['navbar.ausencias.visible', 'navbar.ausencias.editable'],
        ],
        [
            'label' => __('Documentos'),
            'route' => 'backoffice.documentos.index',
            'active' => 'backoffice.documentos.*',
            'icon' => 'fa-solid fa-folder-open',
            'permissions' => ['navbar.documentos.visible', 'navbar.documentos.editable'],
        ],
        [
            'label' => __('Servicios'),
            'route' => 'backoffice.servicios.inventario',
            'active' => 'backoffice.servicios.inventario',
            'icon' => 'fa-solid fa-boxes-stacked',
            'permissions' => ['servicios.ver'],
        ],
        [
            'label' => __('Mapa Servicios'),
            'route' => 'backoffice.servicios.mapa',
            'active' => 'backoffice.servicios.mapa',
            'icon' => 'fa-solid fa-map-location-dot',
            'permissions' => ['servicios.ver'],
        ],
        [
            'label' => __('Seguridad'),
            'route' => 'seguridad.roles.index',
            'active' => 'seguridad.*',
            'icon' => 'fa-solid fa-shield-halved',
            'permissions' => ['navbar.seguridad.visible', 'navbar.seguridad.editable'],
        ],
    ];
@endphp

<nav
    x-data="{
        open: false,
        collapsed: sessionStorage.getItem('boSidebarCollapsed') === 'true',
        toggleCollapsed() {
            this.collapsed = !this.collapsed;
            sessionStorage.setItem('boSidebarCollapsed', this.collapsed ? 'true' : 'false');
        }
    }"
    :class="collapsed ? 'lg:w-20' : 'lg:w-72'"
    class="w-full transition-all duration-300 ease-in-out lg:shrink-0"
>
    <div class="flex h-16 items-center justify-between border-b border-blue-300/25 bg-[#0A2E5D]/90 px-4 lg:hidden">
        <a href="{{ route('dashboard') }}" wire:navigate>
            <x-application-logo class="block h-9 w-auto rounded-md bg-white/85 px-2 py-1" />
        </a>

        <div class="flex items-center gap-2">
            <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-300 hover:bg-slate-800 hover:text-white focus:outline-none transition duration-150 ease-in-out">
            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                <path :class="{ 'hidden': open, 'inline-flex': !open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                <path :class="{ 'hidden': !open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            </button>
        </div>
    </div>

    <aside class="hidden h-screen border-r border-blue-300/25 bg-[#0A2E5D]/90 text-slate-100 transition-all duration-300 ease-in-out lg:sticky lg:top-0 lg:flex lg:flex-col">
        <div class="flex h-16 items-center border-b border-blue-300/25 px-3" :class="collapsed ? 'justify-end' : 'justify-between'">
            <a
                href="{{ route('dashboard') }}"
                wire:navigate
                class="inline-flex items-center justify-center"
                title="{{ __('Inicio') }}"
                x-show="!collapsed"
                x-transition.opacity.duration.200ms
                x-cloak
            >
                <x-application-logo
                    x-bind:class="collapsed ? 'h-7 w-auto rounded-md bg-white/85 px-1.5 py-1 transition-all duration-300 ease-in-out' : 'h-9 w-auto rounded-md bg-white/85 px-2 py-1 transition-all duration-300 ease-in-out'"
                />
            </a>

            <div class="flex items-center gap-1">
                <button
                    @click="toggleCollapsed"
                    type="button"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-md text-slate-300 hover:bg-slate-800 hover:text-white"
                    :title="collapsed ? 'Expandir menu' : 'Colapsar menu'"
                >
                    <i class="fa-solid transition-transform duration-300" :class="collapsed ? 'fa-angles-right' : 'fa-angles-left'"></i>
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto px-3 py-4">
            <div class="space-y-1">
                @foreach ($menuItems as $item)
                    @canany($item['permissions'])
                        @php $isActive = request()->routeIs($item['active']); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            title="{{ $item['label'] }}"
                            :class="collapsed ? 'justify-center' : ''"
                            class="flex items-center rounded-md px-3 py-2 text-sm font-medium transition {{ $isActive ? 'bg-blue-300/15 text-blue-100 ring-1 ring-blue-300/25' : 'text-slate-200 hover:bg-slate-800/60 hover:text-white' }}"
                        >
                            <i class="{{ $item['icon'] }} text-base" :class="collapsed ? '' : 'mr-3'"></i>
                            <span x-show="!collapsed" x-transition.opacity.duration.200ms x-cloak>{{ $item['label'] }}</span>
                        </a>
                    @endcanany
                @endforeach
            </div>
        </div>

        <div class="border-t border-blue-300/25 p-4">
            <div class="mb-3" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-show="!collapsed" x-cloak>
                <p class="text-sm font-medium text-slate-100" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                <p class="text-xs text-slate-300">{{ auth()->user()->email }}</p>
            </div>

            <div class="space-y-1">
                <a
                    href="{{ route('profile') }}"
                    wire:navigate
                    title="{{ __('Perfil') }}"
                    :class="collapsed ? 'justify-center' : ''"
                    class="flex items-center rounded-md px-3 py-2 text-sm text-slate-200 hover:bg-slate-800/60 hover:text-white transition"
                >
                    <i class="fa-solid fa-user text-base" :class="collapsed ? '' : 'mr-3'"></i>
                    <span x-show="!collapsed" x-transition.opacity.duration.200ms x-cloak>{{ __('Perfil') }}</span>
                </a>

                <button
                    wire:click="logout"
                    title="{{ __('Salir') }}"
                    :class="collapsed ? 'justify-center' : ''"
                    class="flex w-full items-center rounded-md px-3 py-2 text-left text-sm text-red-300 hover:bg-red-500/10 hover:text-red-200 transition"
                >
                    <i class="fa-solid fa-right-from-bracket text-base" :class="collapsed ? '' : 'mr-3'"></i>
                    <span x-show="!collapsed" x-transition.opacity.duration.200ms x-cloak>{{ __('Salir') }}</span>
                </button>
            </div>
        </div>
    </aside>

    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-cloak
        class="border-b border-blue-300/25 bg-[#0A2E5D]/90 lg:hidden"
    >
        <div class="space-y-1 px-3 py-3">
            @foreach ($menuItems as $item)
                @canany($item['permissions'])
                    <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['active'])" wire:navigate>
                        <span class="inline-flex items-center gap-2">
                            <i class="{{ $item['icon'] }}"></i>
                            <span>{{ $item['label'] }}</span>
                        </span>
                    </x-responsive-nav-link>
                @endcanany
            @endforeach
        </div>

        <div class="border-t border-blue-300/25 px-4 py-4">
            <div class="mb-3" x-data="{{ json_encode(['name' => auth()->user()->name]) }}">
                <p class="text-base font-medium text-slate-100" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></p>
                <p class="text-sm text-slate-300">{{ auth()->user()->email }}</p>
            </div>

            <div class="space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-user"></i>
                        <span>{{ __('Perfil') }}</span>
                    </span>
                </x-responsive-nav-link>

                <button wire:click="logout" class="block w-full rounded-md px-3 py-2 text-left text-base font-medium text-red-300 hover:bg-red-500/10 hover:text-red-200 transition">
                    <span class="inline-flex items-center gap-2">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <span>{{ __('Salir') }}</span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</nav>
