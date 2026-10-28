@php
    $items = [
        ['route' => 'insumos.solicitudes', 'label' => 'Solicitudes', 'permission' => 'insumos.solicitar'],
        ['route' => 'insumos.gestion', 'label' => 'Gestión', 'permission' => 'insumos.gestionar'],
        ['route' => 'insumos.inventario', 'label' => 'Inventario', 'permission' => 'insumos.inventario'],
        ['route' => 'insumos.importaciones', 'label' => 'Importar stock', 'permission' => 'insumos.importar'],
        ['route' => 'insumos.reportes', 'label' => 'Reportes', 'permission' => 'insumos.reportes'],
        ['route' => 'insumos.notificaciones', 'label' => 'Notificaciones', 'permission' => null],
    ];
@endphp

<nav aria-label="Navegación de Insumos" class="bo-segmented overflow-x-auto p-2">
    <div class="flex min-w-max gap-1">
        @foreach ($items as $item)
            @if (! $item['permission'] || auth()->user()->can($item['permission']))
                <a href="{{ route($item['route']) }}" wire:navigate @class([
                    'bo-segmented-item px-4 py-2 text-sm font-semibold transition',
                    'is-active' => request()->routeIs($item['route']),
                ]) @if (request()->routeIs($item['route'])) aria-current="page" @endif>{{ $item['label'] }}</a>
            @endif
        @endforeach
    </div>
</nav>
