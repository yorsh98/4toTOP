@php
    $authTitle = match (true) {
        request()->routeIs('register') => 'Registro',
        request()->routeIs('password.request') => 'Recuperar contraseña',
        request()->routeIs('password.reset') => 'Restablecer contraseña',
        request()->routeIs('password.confirm') => 'Confirmar contraseña',
        request()->routeIs('verification.notice') => 'Verificar correo',
        default => 'Ingresar',
    };
@endphp

<x-public.layout :title="$authTitle">
    <div class="fo-page fo-auth-page" data-app-view>
        <x-public.page-head :title="$authTitle" :lead="request()->routeIs('login') ? 'Usa tu cuenta del 4toTOP para entrar al backoffice.' : null" />
        <div class="fo-panel fo-auth-panel">
            {{ $slot }}
        </div>
    </div>
</x-public.layout>
