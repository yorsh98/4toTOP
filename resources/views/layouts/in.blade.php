{{-- Páginas públicas internas: usan el mismo marco que la portada --}}
<x-public.layout :title="$title ?? null">
    <div class="fo-page" data-app-view>
        {{ $slot }}
    </div>
</x-public.layout>
