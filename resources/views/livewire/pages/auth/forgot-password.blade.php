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

<div class="fo-auth-content">
    <p class="fo-auth-copy">{{ __('auth.forgot_password_message') }}</p>
    @if (session('status'))
        <div class="fo-alert fo-auth-status" role="status">{{ session('status') }}</div>
    @endif
    <form class="fo-auth-form" wire:submit="sendPasswordResetLink">
        <div>
            <label class="fo-label" for="email">{{ __('Email') }}</label>
            <input class="fo-input" wire:model="email" id="email" type="email" name="email" required autofocus aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
            @error('email') <p class="fo-hint fo-hint--warn" id="email-error">{{ $message }}</p> @enderror
        </div>
        <button class="fo-btn fo-btn--primary" type="submit">{{ __('auth.email_password_reset_link') }}</button>
    </form>
</div>
