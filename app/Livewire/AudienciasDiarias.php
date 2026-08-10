<?php

// app/Livewire/AudienciasDiarias.php

namespace App\Livewire;

use App\Models\Audiencia;
use App\Models\MailRecipient;
use App\Models\MailSignature;
use App\NotificationsV2\Application\Services\ProgramacionDiariaNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AudienciasDiarias extends Component
{
    public string $fecha;

    public $audiencias = [];

    // Slide-over y difusión
    public bool $showSend = false;

    public bool $listModal = false;

    // Lista de este envío (chips)
    public string $newEmail = '';

    public array $recipients = [];

    public string $bulk = '';

    // Lista de BD (administración)
    public array $recipientsDb = [];

    public string $newEmailDb = '';

    // Firmas
    public array $signatures = [];

    public ?int $firmaId = null;

    public string $firmaPreview = '';

    public ?int $envioDispatchId = null;

    public ?string $envioStatus = null;

    public int $envioTotal = 0;

    public int $envioEnviados = 0;

    public int $envioFallidos = 0;

    public array $envioFallidosDetalle = [];

    public bool $envioNotificado = false;

    public function mount(): void
    {
        Gate::authorize('audiencias.ver');
        $this->fecha = now()->toDateString();
        $this->buscar();

        // Firmas activas
        $querySig = method_exists(MailSignature::class, 'scopeActivas')
            ? MailSignature::query()->activas()
            : MailSignature::query()->where('activo', true);

        $this->signatures = $querySig
            ->orderBy('id')->get(['id', 'nombre', 'html'])
            ->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->nombre, 'html' => $s->html])
            ->all();

        if (! empty($this->signatures)) {
            $this->firmaId = $this->signatures[0]['id'];
            $this->firmaPreview = $this->signatures[0]['html'];
        }

        // Cargar lista BD para el modal
        $this->refreshRecipientsFromDb();
    }

    public function updatedFecha(): void
    {
        $this->buscar();
    }

    public function hoy(): void
    {
        $this->fecha = now()->toDateString();
        $this->buscar();
    }

    public function diaAnterior(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->subDay()->toDateString();
        $this->buscar();
    }

    public function diaSiguiente(): void
    {
        $this->fecha = Carbon::parse($this->fecha)->addDay()->toDateString();
        $this->buscar();
    }

    public function buscar(): void
    {
        $this->audiencias = Audiencia::query()
            ->whereDate('fecha', $this->fecha)
            ->orderBy('sala')->orderBy('hora_inicio')
            ->limit(200)->get()->values();

        $this->dispatch('busqueda-lista');
    }

    public function eliminar(int $id): void
    {
        Gate::authorize('audiencias.editar');
        $aud = Audiencia::find($id);
        if (! $aud) {
            return;
        }

        $aud->delete();
        $this->buscar();
        $this->dispatch('audiencia-eliminada');
    }

    /* ===================== Lista del ENVÍO (chips) ===================== */
    public function addRecipient(): void
    {
        $email = trim($this->newEmail);
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (! in_array($email, $this->recipients, true)) {
                $this->recipients[] = $email;
            }
            $this->resetErrorBag('newEmail');
            $this->newEmail = '';
        } else {
            $this->addError('newEmail', 'Correo inválido.');
        }
    }

    public function removeRecipient(int $idx): void
    {
        if (isset($this->recipients[$idx])) {
            array_splice($this->recipients, $idx, 1);
        }
    }

    public function importBulk(): void
    {
        $candidatos = preg_split('/[\s,;]+/', $this->bulk ?? '', -1, PREG_SPLIT_NO_EMPTY);
        $ok = 0;
        foreach ($candidatos as $c) {
            $c = trim($c);
            if (filter_var($c, FILTER_VALIDATE_EMAIL) && ! in_array($c, $this->recipients, true)) {
                $this->recipients[] = $c;
                $ok++;
            }
        }
        if ($ok === 0) {
            $this->addError('bulk', 'No se detectaron correos válidos.');
        }
        $this->bulk = '';
    }

    /* ===================== Lista de la BD (modal) ===================== */
    public function refreshRecipientsFromDb(): void
    {
        $query = method_exists(MailRecipient::class, 'scopeActivos')
            ? MailRecipient::query()->activos()
            : MailRecipient::query()->where('activo', true);

        $this->recipientsDb = $query
            ->orderBy('orden')->orderBy('id')
            ->get(['id', 'email'])
            ->map(fn ($r) => ['id' => $r->id, 'email' => $r->email])
            ->all();
    }

    public function addRecipientToDb(): void
    {
        Gate::authorize('progra.mail.enviar');

        $email = trim($this->newEmailDb);

        if (! $email || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('newEmailDb', 'Correo inválido.');

            return;
        }

        // 1) Si ya está ACTIVO, avisamos y no hacemos nada
        $yaActivo = MailRecipient::query()
            ->where('email', $email)
            ->where('activo', true)
            ->exists();

        if ($yaActivo) {
            $this->addError('newEmailDb', 'Ese correo ya existe en la lista.');

            return;
        }

        // 2) Transacción: o reactivamos (si existe inactivo) o creamos
        DB::transaction(function () use ($email) {
            // bloqueamos por email para evitar race condition
            $row = MailRecipient::query()
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if ($row) {
                // existe pero estaba inactivo -> reactivar
                $row->activo = true;

                if (empty($row->orden) || $row->orden <= 0) {
                    $row->orden = ((int) MailRecipient::max('orden')) + 1;
                }

                $row->save();
            } else {
                // no existe -> crear
                $maxOrden = (int) (MailRecipient::query()->max('orden') ?? 0);

                MailRecipient::create([
                    'email' => $email,
                    'activo' => true,
                    'orden' => $maxOrden + 1,
                ]);
            }
        });

        // limpiar input y refrescar listado del modal
        $this->newEmailDb = '';
        $this->refreshRecipientsFromDb();
    }

    public function removeRecipientFromDb(int $id): void
    {
        Gate::authorize('progra.mail.enviar');

        $row = MailRecipient::find($id);
        if (! $row) {
            return;
        }

        // baja lógica (recomendado)
        $row->activo = false;
        $row->save();

        // quitar también de la lista de ENVÍO si estaba
        $this->recipients = array_values(array_filter($this->recipients, fn ($e) => $e !== $row->email));

        $this->refreshRecipientsFromDb();
    }

    public function openListModal(): void
    {
        Gate::authorize('progra.mail.enviar');

        $this->refreshRecipientsFromDb();
        $this->listModal = true;
    }

    public function loadDbIntoSendList(): void
    {
        Gate::authorize('progra.mail.enviar');

        $dbEmails = array_map(fn ($r) => $r['email'], $this->recipientsDb);
        $this->recipients = array_values(array_unique(array_merge($dbEmails, $this->recipients)));
        // no cierro el modal automáticamente; si quieres, descomenta:
        // $this->listModal = false;
    }

    /* ===================== Firma ===================== */
    public function updatedFirmaId(): void
    {
        $sig = collect($this->signatures)->firstWhere('id', $this->firmaId);
        $this->firmaPreview = $sig ? ($sig['html'] ?? '') : '';
    }

    /* ===================== Envío ===================== */
    public function enviarCorreoDifusion(): void
    {
        Gate::authorize('progra.mail.enviar');

        $limpios = array_values(array_unique(array_filter(
            $this->recipients, fn ($e) => filter_var($e, FILTER_VALIDATE_EMAIL)
        )));

        if (empty($limpios)) {
            $this->addError('recipients', 'Agrega al menos un destinatario válido.');

            return;
        }

        $dispatch = app(ProgramacionDiariaNotifier::class)
            ->notify($this->fecha, $limpios, $this->firmaId);

        $this->envioDispatchId = $dispatch->id;
        $this->envioNotificado = false;
        $this->actualizarEstadoEnvio();
    }

    public function actualizarEstadoEnvio(): void
    {
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
            ->map(fn ($recipient) => [
                'email' => $recipient->email,
                'reason' => $recipient->failure_reason,
            ])
            ->all();

        if (! in_array($this->envioStatus, ['sent', 'failed'], true) || $this->envioNotificado) {
            return;
        }

        $this->envioNotificado = true;
        $this->dispatch('correo-difusion-finalizada',
            status: $this->envioStatus,
            dispatchId: $dispatch->id,
            sent: $this->envioEnviados,
            failed: $this->envioFallidos,
            failures: $this->envioFallidosDetalle,
        );
    }

    /* ===================== Slide-over ===================== */
    public function openSend(): void
    {
        Gate::authorize('progra.mail.enviar');

        // Carga predeterminada de la BD (activos) y mergea con lo ya agregado manualmente
        $query = method_exists(MailRecipient::class, 'scopeActivos')
            ? MailRecipient::query()->activos()
            : MailRecipient::query()->where('activo', true);

        $defaults = $query->orderBy('orden')->orderBy('id')->pluck('email')->all();
        $this->recipients = array_values(array_unique(array_merge($defaults, $this->recipients)));

        $this->showSend = true;
    }

    public function render()
    {
        Gate::authorize('audiencias.ver');

        return view('livewire.audiencias-diarias');
    }
}
