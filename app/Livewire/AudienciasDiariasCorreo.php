<?php

namespace App\Livewire;

use App\Models\MailRecipient;
use App\Models\MailSignature;
use App\NotificationsV2\Application\Services\ProgramacionDiariaNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Reactive;
use Livewire\Component;

class AudienciasDiariasCorreo extends Component
{
    #[Reactive]
    public string $fecha;

    public string $newEmail = '';

    #[Locked]
    public array $recipients = [];

    #[Locked]
    public array $recipientsDb = [];

    public string $newEmailDb = '';

    #[Locked]
    public array $signatures = [];

    public ?int $firmaId = null;

    #[Locked]
    public string $firmaPreview = '';

    #[Locked]
    public ?int $envioDispatchId = null;

    #[Locked]
    public ?string $envioStatus = null;

    #[Locked]
    public int $envioTotal = 0;

    #[Locked]
    public int $envioEnviados = 0;

    #[Locked]
    public int $envioFallidos = 0;

    #[Locked]
    public array $envioFallidosDetalle = [];

    #[Locked]
    public bool $envioNotificado = false;

    public bool $listModal = false;

    public function mount(): void
    {
        Gate::authorize('progra.mail.enviar');

        $this->signatures = MailSignature::query()
            ->where('activo', true)
            ->orderBy('id')
            ->get(['id', 'nombre', 'html'])
            ->map(fn ($signature) => ['id' => $signature->id, 'nombre' => $signature->nombre, 'html' => $signature->html])
            ->all();

        if ($this->signatures !== []) {
            $this->firmaId = $this->signatures[0]['id'];
            $this->firmaPreview = $this->signatures[0]['html'];
        }

        $this->recipients = MailRecipient::query()
            ->where('activo', true)
            ->orderBy('orden')->orderBy('id')
            ->pluck('email')
            ->all();
    }

    public function updatedFirmaId(): void
    {
        $signature = collect($this->signatures)->firstWhere('id', $this->firmaId);
        $this->firmaPreview = $signature ? ($signature['html'] ?? '') : '';
    }

    public function addRecipient(): void
    {
        $email = trim($this->newEmail);
        if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('newEmail', 'Correo inválido.');

            return;
        }

        if (! in_array($email, $this->recipients, true)) {
            $this->recipients[] = $email;
        }

        $this->resetErrorBag('newEmail');
        $this->newEmail = '';
    }

    public function removeRecipient(int $index): void
    {
        if (isset($this->recipients[$index])) {
            array_splice($this->recipients, $index, 1);
        }
    }

    public function refreshRecipientsFromDb(): void
    {
        Gate::authorize('progra.mail.enviar');

        $this->recipientsDb = MailRecipient::query()
            ->where('activo', true)
            ->orderBy('orden')->orderBy('id')
            ->get(['id', 'email'])
            ->map(fn ($recipient) => ['id' => $recipient->id, 'email' => $recipient->email])
            ->all();
    }

    public function openListModal(): void
    {
        $this->refreshRecipientsFromDb();
        $this->listModal = true;
    }

    public function addRecipientToDb(): void
    {
        Gate::authorize('progra.mail.enviar');

        $email = trim($this->newEmailDb);
        if (strlen($email) > 255 || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('newEmailDb', 'Correo inválido.');

            return;
        }

        if (MailRecipient::query()->where('email', $email)->where('activo', true)->exists()) {
            $this->addError('newEmailDb', 'Ese correo ya existe en la lista.');

            return;
        }

        DB::transaction(function () use ($email): void {
            $recipient = MailRecipient::query()->where('email', $email)->lockForUpdate()->first();

            if ($recipient) {
                $recipient->update([
                    'activo' => true,
                    'orden' => $recipient->orden > 0 ? $recipient->orden : ((int) MailRecipient::max('orden')) + 1,
                ]);

                return;
            }

            MailRecipient::create([
                'email' => $email,
                'activo' => true,
                'orden' => ((int) MailRecipient::max('orden')) + 1,
            ]);
        });

        $this->newEmailDb = '';
        $this->refreshRecipientsFromDb();
    }

    public function removeRecipientFromDb(int $id): void
    {
        Gate::authorize('progra.mail.enviar');

        $recipient = MailRecipient::find($id);
        if (! $recipient) {
            return;
        }

        $recipient->update(['activo' => false]);
        $this->recipients = array_values(array_filter($this->recipients, fn ($email) => $email !== $recipient->email));
        $this->refreshRecipientsFromDb();
    }

    public function loadDbIntoSendList(): void
    {
        Gate::authorize('progra.mail.enviar');

        $this->recipients = array_values(array_unique(array_merge(
            array_column($this->recipientsDb, 'email'),
            $this->recipients,
        )));
    }

    public function enviarCorreoDifusion(): void
    {
        Gate::authorize('progra.mail.enviar');

        $this->validate(['fecha' => 'required|date_format:Y-m-d', 'firmaId' => ['nullable', Rule::in(array_column($this->signatures, 'id'))]]);

        $recipients = array_values(array_unique(array_filter(
            $this->recipients,
            fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL),
        )));

        if ($recipients === []) {
            $this->addError('recipients', 'Agrega al menos un destinatario válido.');

            return;
        }

        $dispatch = app(ProgramacionDiariaNotifier::class)->notify($this->fecha, $recipients, $this->firmaId);

        $this->envioDispatchId = $dispatch->id;
        $this->envioNotificado = false;
        $this->actualizarEstadoEnvio();
    }

    public function actualizarEstadoEnvio(): void
    {
        Gate::authorize('progra.mail.enviar');
        if (! $this->envioDispatchId) {
            return;
        }

        $dispatch = NotificationDispatch::query()->find($this->envioDispatchId);
        if (! $dispatch) {
            return;
        }

        $this->envioStatus = $dispatch->status;
        $this->envioTotal = $dispatch->recipients()->count();
        $this->envioEnviados = $dispatch->recipients()->where('status', 'sent')->count();
        $this->envioFallidos = $dispatch->recipients()->where('status', 'failed')->count();
        $this->envioFallidosDetalle = $dispatch->recipients()
            ->where('status', 'failed')
            ->get(['email', 'failure_reason'])
            ->map(fn ($recipient) => ['email' => $recipient->email, 'reason' => $recipient->failure_reason])
            ->all();

        if (in_array($this->envioStatus, ['sent', 'failed'], true) && ! $this->envioNotificado) {
            $this->envioNotificado = true;
            $this->dispatch('correo-difusion-finalizada', status: $this->envioStatus, dispatchId: $dispatch->id, sent: $this->envioEnviados, failed: $this->envioFallidos, failures: $this->envioFallidosDetalle);
        }
    }

    public function close(): void
    {
        $this->dispatch('audiencias-correo-cerrado');
    }

    public function render()
    {
        Gate::authorize('progra.mail.enviar');

        return view('livewire.audiencias-diarias-correo');
    }
}
