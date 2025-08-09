<x-app-layout>

    @push('styles')
        
    @endpush

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel administrativo de GUIAS TELEFONICAS') }}
        </h2>
    </x-slot>
    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          
        </div>
    </div>
    <div class="py-1">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900" >
                <!--ESPACIO PARA MODULO LIVEWIRE -->
                    <livewire:tabla-guias :modo="'full'" />
                    
                </div>
            </div>
        </div>
    </div>
        <!-- Modal para editar guías -->
                    
    
    @push('scripts')

    @endpush
</x-app-layout>
