<?php
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $isFirstUser = ! User::query()->exists();

        $validated['password'] = Hash::make($validated['password']);
        $validated['approved_at'] = $isFirstUser ? now() : null;
        $validated['approved_by'] = null;
        $validated['approval_note'] = $isFirstUser ? 'Aprobacion automatica del primer usuario registrado' : null;
        $validated['rejected_at'] = null;
        $validated['rejected_by'] = null;
        $validated['rejection_note'] = null;

        $user = User::create($validated);

        if ($isFirstUser) {
            $role = Role::firstOrCreate([
                'name' => 'SuperAdmin',
                'guard_name' => 'web',
            ]);

            $user->syncRoles([$role->name]);
        }

        event(new Registered($user));

        session()->flash('status', $isFirstUser
            ? 'Cuenta registrada y aprobada automaticamente por ser el primer usuario del sistema. Ya puedes iniciar sesion.'
            : 'Cuenta registrada. Debe ser aprobada por un administrador antes de iniciar sesion.');
        $this->redirect(route('login', absolute: false), navigate: true);
    }
};
?>

<div class="fo-auth-content">
    <form class="fo-auth-form" wire:submit="register">
        <div>
            <label class="fo-label" for="name">{{ __('auth.name') }}</label>
            <input class="fo-input" wire:model="name" id="name" type="text" name="name" required autofocus autocomplete="name" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @error('name') aria-describedby="name-error" @enderror>
            @error('name') <p class="fo-hint fo-hint--warn" id="name-error">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="fo-label" for="email">{{ __('auth.email') }}</label>
            <input class="fo-input" wire:model="email" id="email" type="email" name="email" required autocomplete="username" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" @error('email') aria-describedby="email-error" @enderror>
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
        <button class="fo-btn fo-btn--primary" type="submit">{{ __('auth.register') }}</button>
        <p>¿Ya tienes cuenta? <a class="fo-auth-link" href="{{ route('login') }}" wire:navigate>Ingresar</a></p>
    </form>
</div>
