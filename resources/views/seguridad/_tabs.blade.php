<nav class="overflow-x-auto" aria-label="Secciones de seguridad">
    <div class="bo-segmented flex w-fit min-w-max gap-1 p-1 text-sm">
        <a href="{{ route('seguridad.roles.index') }}" wire:navigate @class(['bo-segmented-item px-3 py-2 font-medium', 'is-active' => request()->routeIs('seguridad.roles.*')]) @if (request()->routeIs('seguridad.roles.*')) aria-current="page" @endif>
            Roles
        </a>
        @can('navbar.seguridad.editable')
            <a href="{{ route('seguridad.users.index') }}" wire:navigate @class(['bo-segmented-item px-3 py-2 font-medium', 'is-active' => request()->routeIs('seguridad.users.*')]) @if (request()->routeIs('seguridad.users.*')) aria-current="page" @endif>
                Usuarios y roles
            </a>
        @endcan
        <a href="{{ route('seguridad.auditoria.index') }}" wire:navigate @class(['bo-segmented-item px-3 py-2 font-medium', 'is-active' => request()->routeIs('seguridad.auditoria.*')]) @if (request()->routeIs('seguridad.auditoria.*')) aria-current="page" @endif>
            Auditoría
        </a>
    </div>
</nav>
