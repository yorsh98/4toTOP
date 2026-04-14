<div class="mb-4 border-b pb-3">
    <div class="flex flex-wrap gap-2 text-sm">
        <a href="{{ route('seguridad.roles.index') }}" wire:navigate class="rounded-md px-3 py-1.5 {{ request()->routeIs('seguridad.roles.*') ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
            Roles
        </a>
        <a href="{{ route('seguridad.users.index') }}" wire:navigate class="rounded-md px-3 py-1.5 {{ request()->routeIs('seguridad.users.*') ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
            Usuarios y Roles
        </a>
        <a href="{{ route('seguridad.auditoria.index') }}" wire:navigate class="rounded-md px-3 py-1.5 {{ request()->routeIs('seguridad.auditoria.*') ? 'bg-sky-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
            Auditoria
        </a>
    </div>
</div>
