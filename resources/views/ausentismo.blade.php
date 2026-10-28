<x-app-layout>
   
    <x-slot name="header">
        <x-page-heading eyebrow="Personal · Ausencias" title="Ausencias" />
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bo-card overflow-hidden">
                <div class="p-6 bo-strong">
                    <livewire:ausentismo />
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
