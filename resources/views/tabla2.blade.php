<x-in-layout title="Últimas libertades">
    <x-public.page-head title="Últimas libertades" lead="Números de libertad ya entregados, con su causa y solicitante.">
        <a href="{{ route('SistOficioLibertades.index') }}" class="fo-back" data-fo-link>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Oficios y libertades
        </a>
    </x-public.page-head>
    <livewire:libertad-tabla modo="vista" />
</x-in-layout>
