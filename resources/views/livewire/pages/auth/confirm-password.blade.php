<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $password = '';

    /**
     * Confirm the current user's password.
     */
    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'), // Usa la clave de traducción
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
};
?>

<div class="fo-auth-content">
    <p class="fo-auth-copy">{{ __('auth.confirm_password_message') }}</p>
    <form class="fo-auth-form" wire:submit="confirmPassword">
        <div>
            <label class="fo-label" for="password">{{ __('auth.password') }}</label>
            <input class="fo-input" wire:model="password" id="password" type="password" name="password" required autocomplete="current-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="password-error" @enderror>
            @error('password') <p class="fo-hint fo-hint--warn" id="password-error">{{ $message }}</p> @enderror
        </div>
        <button class="fo-btn fo-btn--primary" type="submit">{{ __('auth.confirm') }}</button>
    </form>
</div>
