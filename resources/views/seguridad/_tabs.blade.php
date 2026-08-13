<nav class="border-b border-slate-200" aria-label="Secciones de seguridad">
    <div class="flex gap-1 overflow-x-auto pb-px text-sm">
        <a href="{{ route('seguridad.roles.index') }}" wire:navigate @class(['whitespace-nowrap border-b-2 px-3 py-3 font-medium transition', 'border-sky-700 text-sky-800' => request()->routeIs('seguridad.roles.*'), 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' => ! request()->routeIs('seguridad.roles.*')])>
            Roles
        </a>
        @can('navbar.seguridad.editable')
            <a href="{{ route('seguridad.users.index') }}" wire:navigate @class(['whitespace-nowrap border-b-2 px-3 py-3 font-medium transition', 'border-sky-700 text-sky-800' => request()->routeIs('seguridad.users.*'), 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' => ! request()->routeIs('seguridad.users.*')])>
                Usuarios y roles
            </a>
        @endcan
        <a href="{{ route('seguridad.auditoria.index') }}" wire:navigate @class(['whitespace-nowrap border-b-2 px-3 py-3 font-medium transition', 'border-sky-700 text-sky-800' => request()->routeIs('seguridad.auditoria.*'), 'border-transparent text-slate-600 hover:border-slate-300 hover:text-slate-900' => ! request()->routeIs('seguridad.auditoria.*')])>
            Auditoria
        </a>
    </div>
</nav>
