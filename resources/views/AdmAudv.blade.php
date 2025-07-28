<x-app-layout>
    <x-insertbootstrap />
    <link rel="stylesheet" href="{{ asset('css/audiencias.css') }}">
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Administracion BD Audiencias') }}
        </h2>
    </x-slot>
   
    <div class="py-2">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">                    
                     @livewire('audiencia-form')
                </div>
            </div>
        </div>
    </div>
    
</x-app-layout>
