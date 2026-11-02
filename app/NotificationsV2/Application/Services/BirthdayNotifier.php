<?php

namespace App\NotificationsV2\Application\Services;

use App\Models\Funcionario;
use App\NotificationsV2\Application\Contracts\NotificationOrchestrator;
use App\NotificationsV2\Domain\Models\NotificationTemplate;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\DefaultEmailTemplates;
use App\Services\FuncionarioCatalog;
use Illuminate\Mail\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class BirthdayNotifier
{
    public function __construct(private readonly FuncionarioCatalog $catalog, private readonly NotificationOrchestrator $orchestrator) {}

    public function notify(Carbon $date, bool $dryRun = false): array
    {
        $birthdays = $this->catalog->cumpleanerosDelDia($date);
        if ($birthdays === []) {
            return [];
        }
        $upcoming = [];
        for ($day = 1; $day <= 5; $day++) {
            $next = $date->copy()->addDays($day);
            foreach ($this->catalog->cumpleanerosDelDia($next) as $person) {
                $upcoming[] = ['nombre' => $person['nombre'], 'fecha' => $next->locale('es')->isoFormat('DD [de] MMMM')];
            }
        }
        $birthdayEmails = collect($birthdays)->pluck('email')->filter()->map(fn ($email) => mb_strtolower($email))->all();
        $recipients = Funcionario::query()->whereNotNull('email')->get(['nombre', 'email'])
            ->filter(fn ($person) => filter_var($person->email, FILTER_VALIDATE_EMAIL))
            ->unique(fn ($person) => mb_strtolower($person->email));
        $messages = [];
        foreach ($recipients as $recipient) {
            $isBirthday = in_array(mb_strtolower($recipient->email), $birthdayEmails, true);
            $person = collect($birthdays)->first(fn ($p) => mb_strtolower((string) $p['email']) === mb_strtolower($recipient->email));
            $others = collect($birthdays)->reject(fn ($p) => mb_strtolower((string) $p['email']) === mb_strtolower($recipient->email))->values()->all();
            $type = $isBirthday ? 'saludo' : 'recordatorio';
            $subject = $isBirthday ? 'Te deseamos un Feliz Cumpleaños' : 'Deséales un feliz Cumpleaños';
            $image = asset('img/cumpleanos/'.($isBirthday ? 'cumpleanos.png' : 'Cumplere.png'));
            $html = view('emails.cumpleanos.'.$type, compact('person', 'others', 'birthdays', 'upcoming', 'date', 'image'))->render();
            $messages[] = ['type' => $type, 'email' => $recipient->email, 'name' => $recipient->nombre, 'subject' => $subject, 'html' => $html];
        }
        if (! $dryRun) {
            foreach ($messages as $message) {
                try {
                    $this->send($date, $message);
                } catch (Throwable $exception) {
                    Log::warning('No se pudo procesar un correo de cumpleaños.', ['type' => $message['type']]);
                }
            }
        }

        return $messages;
    }

    private function send(Carbon $date, array $mail): void
    {
        $key = hash('sha256', 'cumpleanos.'.$mail['type'].'.'.$date->toDateString().'.'.mb_strtolower($mail['email']));
        if (config('notifications_v2.enabled', false)) {
            $code = 'cumpleanos.'.$mail['type'];
            $type = NotificationType::query()->firstOrCreate(['code' => $code], ['name' => 'Cumpleaños', 'channel' => 'mail', 'is_active' => true, 'priority' => 'normal']);
            NotificationTemplate::query()->firstOrCreate(['notification_type_id' => $type->id, 'is_active' => true], ['version' => 1, 'subject' => '{{ subject }}', 'body_html' => DefaultEmailTemplates::birthdayHtml(), 'variables_schema' => ['subject', 'html'], 'activated_at' => now()]);
            $this->orchestrator->dispatch($code, ['subject' => $mail['subject'], 'html' => $mail['html'], 'birthday_mail' => true], ['event_key' => $code.'.'.$date->toDateString(), 'idempotency_key' => $key, 'recipients' => [['type' => 'to', 'email' => $mail['email'], 'name' => $mail['name']]]]);

            return;
        }
        if (! Cache::add('birthday.'.$key, true, now()->addDays(2))) {
            return;
        }
        try {
            Mail::send([], [], function (Message $message) use ($mail): void {
                $filename = $mail['type'] === 'saludo' ? 'cumpleanos.png' : 'Cumplere.png';
                $image = $message->embed(public_path('img/cumpleanos/'.$filename));
                $html = str_replace(asset('img/cumpleanos/'.$filename), $image, $mail['html']);
                $message->to($mail['email'], $mail['name'])->subject($mail['subject'])
                    ->from(config('mail.from.address'), '4º Tribunal de Juicio Oral en lo Penal de Santiago')->html($html);
            });
        } catch (Throwable $exception) {
            Cache::forget('birthday.'.$key);
            throw $exception;
        }
    }
}
