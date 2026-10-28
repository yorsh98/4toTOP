<x-app-layout>
    <x-slot name="header"><x-page-heading eyebrow="Backoffice · Inicio" title="Panel de módulos" /></x-slot>
    @php
        $availableModules = collect(app(\App\Services\BackofficeModules::class)->items())->filter(fn (array $module) => auth()->user()->canAny($module['permissions']));
        $today = mb_strtoupper(mb_substr(now('America/Santiago')->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'), 0, 1)).mb_substr(now('America/Santiago')->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY'), 1);
        $weather = app(\App\Services\WeatherService::class)->today();
    @endphp
    <div class="bo-main">
        <section class="bo-hero">
            <p class="bo-hero-date">{{ $today }} · <time x-data="{ time: '' }" x-init="const update = () => time = new Intl.DateTimeFormat('es-CL', { timeZone: 'America/Santiago', hour: '2-digit', minute: '2-digit' }).format(new Date()); update(); setInterval(update, 60000)" x-text="time"></time></p>
            <h1>Bienvenido/a, {{ auth()->user()->name }}</h1>
            <p>Accede a las herramientas habilitadas para tu cuenta y continúa con tu gestión operativa.</p>
            @if ($weather)
                <div class="bo-weather"><x-public.weather-icon :kind="$weather['kind']" :day="$weather['is_day']" /><span>{{ $weather['temperature'] }} °C · {{ $weather['label'] }}</span></div>
            @endif
        </section>
        <section class="mt-8" aria-labelledby="modules-title">
            <div class="bo-section-heading"><div><p class="bo-eyebrow">Accesos autorizados</p><h2 id="modules-title" class="bo-heading-title">Tus módulos</h2></div><span>{{ $availableModules->count() }} disponibles</span></div>
            @if ($availableModules->isNotEmpty())
                <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    @foreach ($availableModules as $module)
                        <a href="{{ route($module['route']) }}" wire:navigate style="--bo-accent: {{ $module['accent'] }}" class="bo-card bo-module">
                            <span class="bo-module-icon"><i class="{{ $module['icon'] }}" aria-hidden="true"></i></span>
                            <span><strong>{{ $module['name'] }}</strong><span class="bo-module-description">{{ $module['description'] }}</span><span class="bo-module-link">Abrir módulo <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span></span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="bo-empty mt-5"><i class="fa-solid fa-lock" aria-hidden="true"></i><h3>No tienes módulos habilitados</h3><p>Solicita al administrador que asigne los permisos necesarios a tu cuenta.</p></div>
            @endif
        </section>
        <aside class="bo-card bo-support mt-8" aria-label="Soporte"><i class="fa-solid fa-life-ring" aria-hidden="true"></i><p>¿Necesitas ayuda o quieres reportar una incidencia? Escribe a <a href="mailto:{{ config('app.support_email') }}">{{ config('app.support_email') }}</a>.</p></aside>
    </div>
</x-app-layout>
