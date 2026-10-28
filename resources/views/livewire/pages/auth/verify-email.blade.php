<?php
use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
            return;
        }

        Auth::user()->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
};
?>

<div class="fo-auth-content">
    <p class="fo-auth-copy">{{ __('auth.verification_message') }}</p>
    @if (session('status') == 'verification-link-sent')
        <div class="fo-alert fo-auth-status" role="status">{{ __('auth.verification_link_sent') }}</div>
    @endif
    <div class="fo-auth-form">
        <button class="fo-btn fo-btn--primary" wire:click="sendVerification">{{ __('auth.resend_verification_email') }}</button>
        <button wire:click="logout" type="submit" class="fo-auth-link">{{ __('auth.log_out') }}</button>
    </div>
</div>
