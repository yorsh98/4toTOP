<?php
// app/Livewire/AudienciasDiarias.php
namespace App\Livewire;

use Livewire\Component;
use App\Models\Audiencia;
use App\Models\MailSignature;
use App\Models\MailRecipient;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Http\Controllers\PrograMailController;

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

    public function mount(): void
    {
        abort_unless(Auth::check(), 403, 'No autorizado.');
        $this->fecha = now()->toDateString();
        $this->buscar();

        // Firmas activas
        $querySig = method_exists(MailSignature::class, 'scopeActivas')
            ? MailSignature::query()->activas()
            : MailSignature::query()->where('activo', true);

        $this->signatures = $querySig
            ->orderBy('id')->get(['id','nombre','html'])
            ->map(fn($s) => ['id'=>$s->id,'nombre'=>$s->nombre,'html'=>$s->html])
            ->all();

        if (!empty($this->signatures)) {
            $this->firmaId = $this->signatures[0]['id'];
            $this->firmaPreview = $this->signatures[0]['html'];
        }

        // Cargar lista BD para el modal
        $this->refreshRecipientsFromDb();
    }

    public function updatedFecha(): void { $this->buscar(); }

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
        abort_unless(Auth::check(), 403);
        $aud = Audiencia::find($id);
        if (!$aud) return;

        $aud->delete();
        $this->buscar();
        $this->dispatch('audiencia-eliminada');
    }

    /* ===================== Lista del ENVÍO (chips) ===================== */
    public function addRecipient(): void
    {
        $email = trim($this->newEmail);
        if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            if (!in_array($email, $this->recipients, true)) {
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
            if (filter_var($c, FILTER_VALIDATE_EMAIL) && !in_array($c, $this->recipients, true)) {
                $this->recipients[] = $c;
                $ok++;
            }
        }
        if ($ok === 0) $this->addError('bulk', 'No se detectaron correos válidos.');
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
            ->get(['id','email'])
            ->map(fn($r) => ['id' => $r->id, 'email' => $r->email])
            ->all();
    }

    public function addRecipientToDb(): void
    {
        $email = trim($this->newEmailDb);
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('newEmailDb', 'Correo inválido.');
            return;
        }

        // Evitar duplicados si ya existe activo
        $exists = MailRecipient::query()->where('email', $email)->where('activo', true)->exists();
        if ($exists) {
            $this->addError('newEmailDb', 'Ese correo ya existe en la lista.');
            return;
        }

        $maxOrden = (int) (MailRecipient::query()->max('orden') ?? 0);

        MailRecipient::create([
            'email'  => $email,
            'activo' => true,
            'orden'  => $maxOrden + 1,
        ]);

        $this->newEmailDb = '';
        $this->refreshRecipientsFromDb();
    }

    public function removeRecipientFromDb(int $id): void
    {
        $row = MailRecipient::find($id);
        if (!$row) return;

        // baja lógica (recomendado)
        $row->activo = false;
        $row->save();

        // quitar también de la lista de ENVÍO si estaba
        $this->recipients = array_values(array_filter($this->recipients, fn($e) => $e !== $row->email));

        $this->refreshRecipientsFromDb();
    }

    public function openListModal(): void
    {
        $this->refreshRecipientsFromDb();
        $this->listModal = true;
    }

    public function loadDbIntoSendList(): void
    {
        $dbEmails = array_map(fn($r) => $r['email'], $this->recipientsDb);
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
        $limpios = array_values(array_unique(array_filter(
            $this->recipients, fn($e)=> filter_var($e, FILTER_VALIDATE_EMAIL)
        )));

        if (empty($limpios)) {
            $this->addError('recipients', 'Agrega al menos un destinatario válido.');
            return;
        }

        app(PrograMailController::class)
            ->enviarProgramacionPorCorreoLista($this->fecha, $limpios, $this->firmaId);

        $this->dispatch('correo-enviado');
    }

    /* ===================== Slide-over ===================== */
    public function openSend(): void
    {
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
        abort_unless(Auth::check(), 403, 'No autorizado.');
        return view('livewire.audiencias-diarias');
    }
}
