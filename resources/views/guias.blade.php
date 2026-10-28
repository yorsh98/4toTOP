<x-app-layout>
    <x-slot name="header">
        <x-page-heading eyebrow="Guías telefónicas · Administración" title="Panel administrativo" />
    </x-slot>
    <div class="py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bo-card">
                <div class="p-4 sm:p-6" >
                <!--ESPACIO PARA MODULO LIVEWIRE -->
                    <livewire:tabla-guias :modo="'full'" />                    
                </div>
            </div>
        </div>
    </div>                    
</x-app-layout>
