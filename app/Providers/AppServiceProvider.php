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
use App\Services\Servicios\Maqueta\ArchivoPlanoMurosRepository;
use App\Services\Servicios\Maqueta\Contracts\PlanoMurosRepository;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class AppServiceProvider extends ServiceProvider
{
    private const FIXED_SUPERADMIN_EMAIL = 'jtroncosor@pjud.cl';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PlanoMurosRepository::class, ArchivoPlanoMurosRepository::class);
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
        Paginator::useBootstrapFive();

        Event::listen(MessageSending::class, function (MessageSending $event): void {
            $messageId = $this->resolveMessageIdFromEmail($event->message);

            Log::channel('mail_audit')->info('mail.sending', [
                'subject' => (string) $event->message->getSubject(),
                'from' => $this->formatAddresses($event->message->getFrom()),
                'to' => $this->formatAddresses($event->message->getTo()),
                'cc' => $this->formatAddresses($event->message->getCc()),
                'bcc' => $this->formatAddresses($event->message->getBcc()),
                'reply_to' => $this->formatAddresses($event->message->getReplyTo()),
                'message_id' => $messageId,
            ]);
        });

        Event::listen(MessageSent::class, function (MessageSent $event): void {
            $message = $event->message;
            $messageId = $event->sent->getMessageId();

            Log::channel('mail_audit')->info('mail.sent', [
                'subject' => (string) $message->getSubject(),
                'from' => $this->formatAddresses($message->getFrom()),
                'to' => $this->formatAddresses($message->getTo()),
                'cc' => $this->formatAddresses($message->getCc()),
                'bcc' => $this->formatAddresses($message->getBcc()),
                'reply_to' => $this->formatAddresses($message->getReplyTo()),
                'message_id' => $messageId,
            ]);
        });

        Gate::before(function ($user, string $ability) {
            if ($user->email === self::FIXED_SUPERADMIN_EMAIL) {
                return true;
            }

            return $user->hasRole('SuperAdmin') ? true : null;
        });

        Gate::policy(Guias::class, GuiasPolicy::class);
    }

    private function formatAddresses(?array $addresses): array
    {
        if (! is_array($addresses) || $addresses === []) {
            return [];
        }

        return array_values(array_map(static function (Address|string $address): array|string {
            if ($address instanceof Address) {
                return [
                    'name' => $address->getName(),
                    'email' => $address->getAddress(),
                ];
            }

            return (string) $address;
        }, $addresses));
    }

    private function resolveMessageIdFromEmail(Email $message): ?string
    {
        $header = $message->getHeaders()->get('Message-ID');

        if ($header === null) {
            return null;
        }

        return method_exists($header, 'getBodyAsString')
            ? trim((string) $header->getBodyAsString())
            : trim((string) $header->toString());
    }
}
