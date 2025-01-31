<?php
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Enviamos el enlace de restablecimiento de contraseña al usuario
        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status !== Password::RESET_LINK_SENT) {
            $this->addError('email', __($status)); // Usa la clave de traducción
            return;
        }

        $this->reset('email');
        session()->flash('status', __($status)); // Usa la clave de traducción
    }
};
?>

<div>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('auth.forgot_password_message') }} <!-- Usa la clave de traducción -->
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" /> <!-- Usa la clave de traducción -->
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('auth.email_password_reset_link') }} <!-- Usa la clave de traducción -->
            </x-primary-button>
        </div>
    </form>
</div>