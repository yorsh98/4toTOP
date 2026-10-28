<x-app-layout>
    <x-slot name="header">
        <x-page-heading eyebrow="Backoffice · Cuenta" title="Perfil" />
    </x-slot>

    <div class="py-2">
        <div class="max-w-7xl mx-auto space-y-4 px-4 sm:px-6 lg:px-8">
            <div class="bo-card bo-section p-6">
                <div class="max-w-xl">
                    <livewire:profile.update-profile-information-form />
                </div>
            </div>

            <div class="bo-card bo-section p-6">
                <div class="max-w-xl">
                    <livewire:profile.update-password-form />
                </div>
            </div>

            <div class="bo-card bo-section bo-section--danger p-6">
                <div class="max-w-xl">
                    <livewire:profile.delete-user-form />
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
