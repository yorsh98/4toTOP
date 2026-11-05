<?php
use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        // Mismo invariante que UsersRolesPage: nunca dejar el sistema sin SuperAdmin ni sin usuarios.
        $isLastSuperAdmin = $user->hasRole('SuperAdmin') && User::role('SuperAdmin')->count() <= 1;

        if ($isLastSuperAdmin || User::query()->count() <= 1) {
            throw ValidationException::withMessages([
                'password' => 'No se puede eliminar la última cuenta de administración del sistema.',
            ]);
        }

        tap($user, $logout(...))->delete();
        $this->redirect('/', navigate: true);
    }
};
?>

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium bo-strong">
            {{ __('auth.delete_account') }}
        </h2>
        <p class="mt-1 text-sm bo-muted">
            {{ __('auth.delete_account_warning') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >
        {{ __('auth.delete_account') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="text-lg font-medium bo-strong">
                {{ __('auth.confirm_delete_account') }}
            </h2>
            <p class="mt-1 text-sm bo-muted">
                {{ __('auth.confirm_delete_account_details') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="{{ __('auth.password') }}" class="sr-only" />
                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="{{ __('auth.password') }}"
                />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('auth.cancel') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('auth.delete_account') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>