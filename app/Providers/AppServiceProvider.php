<?php

namespace App\Providers;

use App\Models\Guias;
use App\NotificationsV2\Application\Contracts\ChannelSender;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Application\Contracts\RecipientResolver;
use App\NotificationsV2\Application\Contracts\TemplateRenderer;
use App\NotificationsV2\Application\Services\CatalogRecipientResolver;
use App\NotificationsV2\Application\Services\DatabaseNotificationOrchestrator;
use App\NotificationsV2\Application\Services\SimpleTemplateRenderer;
use App\NotificationsV2\Infrastructure\Mail\MailChannelSender;
use App\Policies\GuiasPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const FIXED_SUPERADMIN_EMAIL = 'jtroncosor@pjud.cl';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(NotificationOrchestrator::class, DatabaseNotificationOrchestrator::class);
        $this->app->bind(RecipientResolver::class, CatalogRecipientResolver::class);
        $this->app->bind(TemplateRenderer::class, SimpleTemplateRenderer::class);
        $this->app->bind(ChannelSender::class, MailChannelSender::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, string $ability) {
            if ($user->email === self::FIXED_SUPERADMIN_EMAIL) {
                return true;
            }

            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::policy(Guias::class, GuiasPolicy::class);
    }
}
