@php
    $items = [
        ['route' => 'insumos.solicitudes', 'label' => 'Solicitudes', 'permission' => 'insumos.solicitar'],
        ['route' => 'insumos.gestion', 'label' => 'Gestión', 'permission' => 'insumos.gestionar'],
        ['route' => 'insumos.inventario', 'label' => 'Inventario', 'permission' => 'insumos.inventario'],
        ['route' => 'insumos.importaciones', 'label' => 'Importar stock', 'permission' => 'insumos.importar'],
        ['route' => 'insumos.reportes', 'label' => 'Reportes', 'permission' => 'insumos.reportes'],
    ];
@endphp

<nav aria-label="Navegación de Insumos" class="overflow-x-auto rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
    <div class="flex min-w-max gap-1">
        @foreach ($items as $item)
            @can($item['permission'])
                <a href="{{ route($item['route']) }}" wire:navigate @class([
                    'rounded-lg px-4 py-2 text-sm font-semibold transition',
                    'bg-cyan-700 text-white shadow-sm' => request()->routeIs($item['route']),
                    'text-slate-600 hover:bg-cyan-50 hover:text-cyan-800' => ! request()->routeIs($item['route']),
                ])>{{ $item['label'] }}</a>
            @endcan
        @endforeach
    </div>
</nav>
