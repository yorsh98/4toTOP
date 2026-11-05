<?php
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    #[Locked]
    public string $token = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email');
    }

    /**
     * Reset the password for the given user.
     */
    public function resetPassword(): void
    {
        $this->validate([
            'token' => ['required'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        // Intentamos restablecer la contraseña del usuario
        $status = Password::reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) {
                $user->forceFill([
                    'password' => Hash::make($this->password),
                    'remember_token' => Str::random(60),
                ])->save();
                // Un restablecimiento cierra toda sesión previa (por si la cuenta estaba comprometida).
                $user->revokeAccess();
                event(new PasswordReset($user));
            }
        );

        // Si el restablecimiento falla, mostramos el mensaje de error
        if ($status !== Password::PASSWORD_RESET) {
            $this->addError('email', __($status)); // Usa la clave de traducción
            return;
        }

        Session::flash('status', __($status)); // Usa la clave de traducción
        $this->redirectRoute('login', navigate: true);
    }
};
?>

<div class="fo-auth-content">
    <form class="fo-auth-form" wire:submit="resetPassword">
        <div>
            <label class="fo-label" for="email">{{ __('auth.email') }}</label>
            <input class="fo-input" wire:model="email" id="email" type="email" name="email" required autofocus autocomplete="username" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
            @error('email') <p class="fo-hint fo-hint--warn" id="email-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="fo-label" for="password">{{ __('auth.password') }}</label>
            <input class="fo-input" wire:model="password" id="password" type="password" name="password" required autocomplete="new-password" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" @error('password') aria-describedby="password-error" @enderror>
            @error('password') <p class="fo-hint fo-hint--warn" id="password-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="fo-label" for="password_confirmation">{{ __('auth.confirm_password') }}</label>
            <input class="fo-input" wire:model="password_confirmation" id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" aria-invalid="{{ $errors->has('password_confirmation') ? 'true' : 'false' }}" @error('password_confirmation') aria-describedby="password_confirmation-error" @enderror>
            @error('password_confirmation') <p class="fo-hint fo-hint--warn" id="password_confirmation-error">{{ $message }}</p> @enderror
        </div>
        <button class="fo-btn fo-btn--primary" type="submit">{{ __('auth.reset_password') }}</button>
    </form>
</div>
