<x-app-layout>
    <x-slot name="header">
        <x-page-heading eyebrow="Backoffice · Inicio" title="Panel de módulos" />
    </x-slot>

    @php
        $modules = [
            ['name' => 'Funcionarios', 'description' => 'Administra el catálogo de funcionarios.', 'route' => 'addfuncionario.index', 'icon' => 'fa-users', 'permissions' => ['navbar.funcionarios.visible', 'navbar.funcionarios.editable'], 'accent' => '#0284c7'],
            ['name' => 'Oficios / Libertades', 'description' => 'Gestiona solicitudes, oficios y libertades.', 'route' => 'oficiolibertad', 'icon' => 'fa-file-signature', 'permissions' => ['navbar.oficios_libertades.visible', 'navbar.oficios_libertades.editable'], 'accent' => '#2563eb'],
            ['name' => 'Turnos', 'description' => 'Consulta y asigna turnos semanales.', 'route' => 'turno', 'icon' => 'fa-calendar-days', 'permissions' => ['navbar.turnos.visible', 'navbar.turnos.editable'], 'accent' => '#4f46e5'],
            ['name' => 'Guías', 'description' => 'Mantén actualizada la guía telefónica.', 'route' => 'guias', 'icon' => 'fa-book', 'permissions' => ['navbar.guias.visible', 'navbar.guias.editable'], 'accent' => '#0891b2'],
            ['name' => 'Audiencias', 'description' => 'Organiza la programación y gestión diaria.', 'route' => 'AdmAudv', 'icon' => 'fa-gavel', 'permissions' => ['navbar.audiencias.visible', 'navbar.audiencias.editable'], 'accent' => '#7c3aed'],
            ['name' => 'TTYPP', 'description' => 'Registra y consulta causas TTYPP.', 'route' => 'ttypp.index', 'icon' => 'fa-user-shield', 'permissions' => ['navbar.ttypp.visible', 'navbar.ttypp.editable'], 'accent' => '#c026d3'],
            ['name' => 'Bitácora de Pruebas', 'description' => 'Documenta y revisa controles operativos.', 'route' => 'bitacora-pruebas.index', 'icon' => 'fa-clipboard-list', 'permissions' => ['navbar.bitacora_pruebas.visible', 'navbar.bitacora_pruebas.editable'], 'accent' => '#059669'],
            ['name' => 'Ausencias', 'description' => 'Registra y consulta ausentismo del personal.', 'route' => 'ausentismo', 'icon' => 'fa-user-clock', 'permissions' => ['navbar.ausencias.visible', 'navbar.ausencias.editable'], 'accent' => '#d97706'],
            ['name' => 'Documentos', 'description' => 'Gestiona documentos y procedimientos.', 'route' => 'backoffice.documentos.index', 'icon' => 'fa-folder-open', 'permissions' => ['navbar.documentos.visible', 'navbar.documentos.editable'], 'accent' => '#ea580c'],
            ['name' => 'Audios de Salas', 'description' => 'Consulta y administra audios de salas.', 'route' => 'audios-salas.index', 'icon' => 'fa-headphones', 'permissions' => ['audios_salas.ver'], 'accent' => '#e11d48'],
            ['name' => 'Visita Carcel', 'description' => 'Centraliza la gestion de visitas a recinto penitenciario.', 'route' => 'visita-carcel.index', 'icon' => 'fa-building-shield', 'permissions' => ['navbar.visita_carcel.visible', 'visita_carcel.ver'], 'accent' => '#be123c'],
            ['name' => 'Servicios', 'description' => 'Administra el inventario de servicios.', 'route' => 'backoffice.servicios.inventario', 'icon' => 'fa-boxes-stacked', 'permissions' => ['servicios.ver'], 'accent' => '#0d9488'],
            ['name' => 'Mapa de Servicios', 'description' => 'Explora la cobertura de servicios.', 'route' => 'backoffice.servicios.mapa', 'icon' => 'fa-map-location-dot', 'permissions' => ['servicios.ver'], 'accent' => '#65a30d'],
            ['name' => 'Insumos', 'description' => 'Solicita y consulta los insumos disponibles.', 'route' => 'insumos.solicitudes', 'icon' => 'fa-box-open', 'permissions' => ['navbar.insumos.visible', 'navbar.insumos.editable', 'insumos.solicitar'], 'accent' => '#0891b2'],
            ['name' => 'Seguridad', 'description' => 'Administra roles, usuarios y auditoría.', 'route' => 'seguridad.roles.index', 'icon' => 'fa-shield-halved', 'permissions' => ['navbar.seguridad.visible', 'navbar.seguridad.editable'], 'accent' => '#475569'],
        ];

        $availableModules = collect($modules)->filter(fn (array $module) => auth()->user()->canAny($module['permissions']));
        $today = now()->locale('es')->isoFormat('dddd, D [de] MMMM');
    @endphp

    <div class="py-6 sm:py-8">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <section class="overflow-hidden rounded-2xl bg-[#0A2E5D] shadow-lg shadow-blue-950/10">
                <div class="relative px-6 py-8 sm:px-10 sm:py-10">
                    <div class="absolute -right-10 -top-16 h-48 w-48 rounded-full border-[28px] border-sky-300/10"></div>
                    <div class="absolute -bottom-24 right-24 h-48 w-48 rounded-full bg-blue-400/10"></div>

                    <div class="relative max-w-3xl">
                        <p class="font-mono text-xs font-bold uppercase tracking-[0.2em] text-sky-200">{{ $today }}</p>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight text-white sm:text-4xl">Bienvenido/a, {{ auth()->user()->name }}</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-blue-100 sm:text-base">Accede a las herramientas habilitadas para tu cuenta y continúa con tu gestión operativa.</p>
                    </div>
                </div>
            </section>

            <section class="mt-8" aria-labelledby="modules-title">
                <div class="flex items-end justify-between gap-4 border-b border-slate-200 pb-4">
                    <div>
                        <p class="font-mono text-xs font-bold uppercase tracking-[0.16em] text-sky-700">Accesos autorizados</p>
                        <h2 id="modules-title" class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">Tus módulos</h2>
                    </div>
                    <p class="hidden text-sm text-slate-500 sm:block">{{ $availableModules->count() }} disponibles</p>
                </div>

                @if ($availableModules->isNotEmpty())
                    <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($availableModules as $module)
                            <a href="{{ route($module['route']) }}" wire:navigate style="--module-accent: {{ $module['accent'] }}; --module-tint: color-mix(in srgb, {{ $module['accent'] }} 8%, white);" class="group relative overflow-hidden rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-[var(--module-accent)] hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-600 focus-visible:ring-offset-2">
                                <div class="absolute inset-y-0 left-0 w-1 bg-[var(--module-accent)]"></div>
                                <div class="flex items-start gap-4 pl-2">
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-[var(--module-tint)] text-[var(--module-accent)] transition group-hover:bg-[var(--module-accent)] group-hover:text-white">
                                        <i class="fa-solid {{ $module['icon'] }} text-lg" aria-hidden="true"></i>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <h3 class="font-semibold text-slate-900">{{ $module['name'] }}</h3>
                                        <p class="mt-1 text-sm leading-5 text-slate-600">{{ $module['description'] }}</p>
                                        <span class="mt-4 inline-flex items-center gap-2 text-sm font-semibold text-[var(--module-accent)]">Abrir módulo <i class="fa-solid fa-arrow-right text-xs transition-transform group-hover:translate-x-1" aria-hidden="true"></i></span>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="mt-5 rounded-xl border border-dashed border-slate-300 bg-white px-6 py-10 text-center">
                        <i class="fa-solid fa-lock text-2xl text-slate-400" aria-hidden="true"></i>
                        <h3 class="mt-3 font-semibold text-slate-900">No tienes módulos habilitados</h3>
                        <p class="mt-1 text-sm text-slate-600">Solicita al administrador que asigne los permisos necesarios a tu cuenta.</p>
                    </div>
                @endif
            </section>

            <aside class="mt-8 flex items-start gap-3 rounded-xl border border-sky-100 bg-sky-50 px-5 py-4 text-sm text-slate-700" aria-label="Soporte">
                <i class="fa-solid fa-life-ring mt-0.5 text-sky-700" aria-hidden="true"></i>
                <p>¿Necesitas ayuda o quieres reportar una incidencia? Escribe a <a href="mailto:jtroncosor@pjud.cl" class="font-semibold text-sky-800 underline decoration-sky-300 underline-offset-2 hover:text-sky-950">jtroncosor@pjud.cl</a>.</p>
            </aside>
        </div>
    </div>
</x-app-layout>
