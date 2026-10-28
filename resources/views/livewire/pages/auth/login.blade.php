<?php
use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();
        $this->form->authenticate();
        Session::regenerate();
        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
};
?>

<div class="fo-auth-content">
    @if (session('status'))
        <div class="fo-alert fo-auth-status" role="status">{{ session('status') }}</div>
    @endif
    <form class="fo-auth-form" wire:submit="login">
        <div>
            <label class="fo-label" for="email">{{ __('auth.email') }}</label>
            <input class="fo-input" wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" aria-invalid="{{ $errors->has('form.email') ? 'true' : 'false' }}" @error('form.email') aria-describedby="email-error" @enderror>
            @error('form.email') <p class="fo-hint fo-hint--warn" id="email-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="fo-label" for="password">{{ __('auth.password') }}</label>
            <input class="fo-input" wire:model="form.password" id="password" type="password" name="password" required autocomplete="current-password" aria-invalid="{{ $errors->has('form.password') ? 'true' : 'false' }}" @error('form.password') aria-describedby="password-error" @enderror>
            @error('form.password') <p class="fo-hint fo-hint--warn" id="password-error">{{ $message }}</p> @enderror
        </div>
        <label class="fo-auth-remember" for="remember">
            <input wire:model="form.remember" id="remember" type="checkbox" name="remember">
            <span>{{ __('auth.remember_me') }}</span>
        </label>
        @if (Route::has('password.request'))
            <a class="fo-auth-link" href="{{ route('password.request') }}" wire:navigate>{{ __('auth.forgot_password') }}</a>
        @endif
        <button class="fo-btn fo-btn--primary" type="submit">{{ __('auth.login') }}</button>
        @if (Route::has('register'))
            <p>¿No tienes cuenta? <a class="fo-auth-link" href="{{ route('register') }}" wire:navigate>Crear una cuenta</a></p>
        @endif
    </form>
</div>
