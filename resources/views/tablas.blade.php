<x-in-layout title="Últimos oficios">
    <x-public.page-head title="Últimos oficios" lead="Números de oficio ya entregados, con su causa y solicitante.">
        <a href="{{ route('SistOficioLibertades.index') }}" class="fo-back" data-fo-link>
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Oficios y libertades
        </a>
    </x-public.page-head>
    <livewire:oficio-tabla modo="vista" />
</x-in-layout>
