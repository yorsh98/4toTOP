<?php

use App\Models\Ausentismo;
use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\Sector;
use App\Models\TtyppRegistro;
use App\Models\User;
use App\Models\VisitaCarcelCausa;
use App\NotificationsV2\Application\Services\BitacoraPruebasNotifier;
use App\NotificationsV2\Application\Services\LegacyUserAccountStatusNotifier;
use App\NotificationsV2\Application\Services\TurnoOneWeeklySummaryNotifier;
use App\NotificationsV2\Application\Services\TurnoTwoAcdWeeklySummaryNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationType;
use App\NotificationsV2\Support\OutlookEmailTemplates;
use App\Services\InsumoGestionService;
use App\Services\InsumoSolicitudService;
use Database\Seeders\ServiciosRoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Email;

beforeEach(function () {
    config([
        'mail.default' => 'array',
        'mail.from' => ['address' => 'general@example.test', 'name' => 'Programación Diaria 4°TOP'],
        'mail.notifications_from' => ['address' => 'notificaciones@example.test', 'name' => 'Notificaciones 4°TOP'],
        'notifications_v2.mailer' => 'array',
        'notifications_v2.enabled' => true,
        'notifications_v2.queue.connection' => 'sync',
        'queue.default' => 'sync',
    ]);
});

function funcionarioCorreoIntegracion(string $email, string $rol = 'jefatura', string $nombre = 'Funcionario de prueba', string $cumpleanos = '1980-01-01'): Funcionario
{
    return Funcionario::query()->create([
        'nombre' => $nombre,
        'email' => $email,
        'rol' => $rol,
        'fecha_cumpleanos' => $cumpleanos,
    ]);
}

function assertCorreoOutlook(string $email, string $subject, ?string $code = null, bool $birthday = false): Email
{
    $message = Mail::mailer('array')->getSymfonyTransport()->messages()
        ->map(fn (SentMessage $sent) => $sent->getOriginalMessage())
        ->sole(fn (Email $message) => $message->getSubject() === $subject && $message->getTo()[0]->getAddress() === $email);

    expect(array_map(fn ($address) => $address->getAddress(), $message->getTo()))->toBe([$email])
        ->and($message->getSubject())->toBe($subject)
        ->and($message->getFrom()[0]->getAddress())->toBe($birthday ? 'general@example.test' : 'notificaciones@example.test')
        ->and($message->getFrom()[0]->getName())->toBe($birthday ? '4º Tribunal de Juicio Oral en lo Penal de Santiago' : 'Notificaciones 4°TOP')
        ->and($message->getHtmlBody())->toContain('Correo automático del sistema 4°TOP', 'PODER JUDICIAL · REPÚBLICA DE CHILE', '4° Tribunal de Juicio Oral en lo Penal de Santiago')
        ->not->toContain('{{ ');

    if ($code !== null) {
        $dispatch = NotificationDispatch::query()
            ->whereHas('notificationType', fn ($type) => $type->where('code', $code))
            ->whereHas('recipients', fn ($recipient) => $recipient->where('email', $email))
            ->sole();
        expect($dispatch->status)->toBe('sent')
            ->and($dispatch->recipients()->where('email', $email)->sole()->status)->toBe('sent');

        if (! $birthday) {
            expect($message->getTextBody())->toContain('Correo automático del sistema 4°TOP')->not->toContain('{{ ');
        }
    }

    return $message;
}

test('el comando de ausentismo remite el resumen Outlook con las ausencias del día', function () {
    $recipient = funcionarioCorreoIntegracion('jefatura@example.test');
    Ausentismo::query()->create([
        'funcionario_nombre' => 'Persona ausente',
        'cargo' => 'Administrativo/a',
        'tipo_permiso' => 'Licencia Medica',
        'fecha_inicio' => '2026-04-20',
        'fecha_termino' => '2026-04-22',
    ]);

    $this->artisan('notificaciones:ausentismo-diario', ['--date' => '2026-04-21'])->assertSuccessful();

    $message = assertCorreoOutlook($recipient->email, 'Resumen diario de ausentismo - 2026-04-21', 'ausentismo.resumen_diario');
    expect($message->getHtmlBody())->toContain('Persona ausente');
});

test('el comando de revisión de PP remite el listado Outlook a jefatura', function () {
    $recipient = funcionarioCorreoIntegracion('jefatura@example.test');
    $causa = VisitaCarcelCausa::query()->create(['ruc' => '2301234567-8', 'rit' => '142-2026']);
    $causa->imputados()->create([
        'nombre' => 'Persona en revisión',
        'rut' => '12.345.678-9',
        'penal' => 'Santiago 1',
        'delito' => 'Robo',
        'fecha_ingreso_pp' => '2026-01-01',
        'ultima_rev_pp' => '2026-03-01',
    ]);

    $this->artisan('notificaciones:visita-carcel-revision-pp', ['fecha' => '2026-08-20'])->assertSuccessful();

    $message = assertCorreoOutlook($recipient->email, 'Revisión de PP - 20/08/2026', 'visita_carcel.revision_pp');
    expect($message->getHtmlBody())->toContain('Persona en revisión', $causa->rit);
});

test('el comando TTyPP remite los registros de la fecha al catálogo TTyPP', function () {
    $recipient = funcionarioCorreoIntegracion('ttypp@example.test', 'ttypp');
    TtyppRegistro::query()->create([
        'rit' => '777-2026', 'ruc' => '2301234567-8', 'tipo_audiencia' => 'Juicio oral',
        'numero_dias' => '2', 'cantidad' => '3', 'modalidad' => 'Presencial', 'encargada_ti_mp' => 'Rosa',
        'encargada_ti_d' => 'Pablo', 'encargada_zoom' => 'Julia', 'acta' => 'Elena', 'sala' => '701',
        'jueces' => "Juez 1\nJuez 2", 'fecha_asignacion' => '2026-05-30',
    ]);
    TtyppRegistro::query()->create(['rit' => '778-2026', 'fecha_asignacion' => '2026-05-30']);

    $this->artisan('notificaciones:ttypp-diario', ['fecha' => '2026-05-30'])->assertSuccessful();

    $message = assertCorreoOutlook($recipient->email, 'Listado TTyPP - 2026-05-30', 'ttypp.listado.diario');
    expect($message->getHtmlBody())->toContain('777-2026')
        ->and($message->getTextBody())->toContain(
            "778-2026 | - | - | - | - | - | - | - | - | - | - | -\n"
            .'777-2026 | 2301234567-8 | Juicio oral | 2 | 3 | Presencial | Rosa | Pablo | Julia | Elena | 701 | Juez 1 Juez 2',
        );
});

test('la bitácora remite los enlaces y el RIT en el correo Outlook', function () {
    $email = 'bitacora@example.test';
    $documentos = 'https://example.test/documentos';
    $bitacora = 'https://example.test/bitacora';

    app(BitacoraPruebasNotifier::class)->notify([$email], 'Remitente de prueba', $documentos, $bitacora, '123-2026');

    $message = assertCorreoOutlook($email, 'Notificacion de Bitacora de Pruebas', 'bitacora_pruebas.notificacion');
    expect($message->getHtmlBody())->toContain($documentos, $bitacora, '123-2026');
});

test('el estado de cuenta conserva el asunto de cada transición', function (string $status, string $subject) {
    $user = User::factory()->create();

    app(LegacyUserAccountStatusNotifier::class)->notify($user, $status, 'Administración de prueba');

    $message = assertCorreoOutlook($user->email, $subject, 'legacy.user_account_status');
    expect($message->getHtmlBody())->toContain($user->name, 'Administración de prueba');
})->with([
    ['approved', 'Tu cuenta fue aprobada'],
    ['rejected', 'Tu cuenta fue rechazada'],
    ['suspended', 'Tu cuenta fue suspendida'],
    ['unsuspended', 'Tu cuenta fue reactivada'],
]);

test('los servicios de insumos notifican la solicitud, la entrega y el cruce de stock mínimo', function () {
    $this->seed(ServiciosRoleSeeder::class);
    $servicios = User::factory()->create();
    $servicios->assignRole('servicios');
    $recipient = User::factory()->create();
    $sector = Sector::factory()->create();
    $insumo = Insumo::query()->create([
        'codigo' => 'CORREO-001', 'nombre' => 'Caja de papel', 'unidad_medida' => 'UN', 'stock_minimo' => 5,
    ]);
    InsumoExistencia::query()->create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 6]);

    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $insumo->id, 'cantidad' => 2]], $recipient->id, null, 'Sala 701');
    app(InsumoGestionService::class)->entregar($solicitud, [[
        'solicitud_detalle_id' => $solicitud->detalles->sole()->id,
        'sector_id' => $sector->id,
        'cantidad' => 2,
    ]], $servicios->id);

    $solicitado = assertCorreoOutlook($servicios->email, 'Nueva solicitud de insumos', 'insumo.solicitado');
    $asignado = assertCorreoOutlook($recipient->email, 'Asignacion de insumos', 'insumo.asignado');
    assertCorreoOutlook($servicios->email, 'Asignacion de insumos', 'insumo.asignado');
    $stock = assertCorreoOutlook($servicios->email, 'Stock bajo de insumos', 'insumo.stock_bajo');

    expect($solicitado->getHtmlBody())->toContain('Caja de papel', 'Sala 701')
        ->and($asignado->getHtmlBody())->toContain('Caja de papel')
        ->and($stock->getHtmlBody())->toContain('quedan 4 UN');
});

test('los turnos remiten las filas de funcionarios con formato Outlook y alternativa de texto', function (int $turno, string $notifier, string $code, string $subject) {
    $recipient = funcionarioCorreoIntegracion('equipo@example.test', 'sala_causa', 'FUNCIONARIO DE TURNO');
    $juez = funcionarioCorreoIntegracion('juez@example.test', 'juez', 'JUEZ DE TURNO');
    $asignaciones = $turno === 1
        ? ['TM1' => $juez->nombre, 'TF1' => $recipient->nombre]
        : ['ACDM1' => $juez->nombre, 'ACDF1' => $recipient->nombre];
    DB::table('turno')->insert(['id' => $turno, 'FECHA' => 'Semana del 13-04-2026 al 17-04-2026', ...$asignaciones]);

    app($notifier)->notifyForWeekWithResult('2026-04-16');

    $message = assertCorreoOutlook($recipient->email, $subject, $code);
    assertCorreoOutlook($juez->email, $subject, $code);
    expect($message->getHtmlBody())->toContain($recipient->nombre, $juez->nombre, 'mso-line-height-rule:exactly;')
        ->and($message->getTextBody())->toContain($recipient->nombre, $juez->nombre);
})->with([
    [1, TurnoOneWeeklySummaryNotifier::class, 'turno1.resumen_semanal', 'Turno Semanal - Semana 13-04-2026 al 17-04-2026'],
    [2, TurnoTwoAcdWeeklySummaryNotifier::class, 'turno2.acd.resumen_semanal', 'Turno 2 ACD - Periodo 13-04-2026 al 17-04-2026'],
]);

test('el comando de cumpleaños incrusta la imagen en saludo y recordatorio', function (bool $enabled) {
    config(['notifications_v2.enabled' => $enabled]);
    $birthday = funcionarioCorreoIntegracion('cumple@example.test', nombre: "O'Connor <b>", cumpleanos: '1980-03-05');
    $other = funcionarioCorreoIntegracion('recordatorio@example.test', nombre: 'Persona informada');

    $this->artisan('notificaciones:cumpleanos', ['fecha' => '2026-03-05'])->assertSuccessful();

    $greeting = assertCorreoOutlook($birthday->email, 'Te deseamos un Feliz Cumpleaños', $enabled ? 'cumpleanos.saludo' : null, birthday: true);
    $reminder = assertCorreoOutlook($other->email, 'Deséales un feliz Cumpleaños', $enabled ? 'cumpleanos.recordatorio' : null, birthday: true);

    foreach ([$greeting, $reminder] as $message) {
        $attachment = collect($message->getAttachments())->sole();
        expect($attachment->getDisposition())->toBe('inline')
            ->and($message->getHtmlBody())->toContain('src="cid:'.$attachment->getContentId().'"', e($birthday->nombre))
            ->not->toContain(e(e($birthday->nombre)), $birthday->nombre, asset('img/cumpleanos/cumpleanos.png'), asset('img/cumpleanos/Cumplere.png'));
    }
})->with([true, false]);

test('el broker de contraseñas envía el enlace Outlook con token y email válidos', function () {
    config(['auth.passwords.users.expire' => 37]);
    $user = User::factory()->create(['name' => 'Ana & Luis']);

    expect(Password::sendResetLink(['email' => $user->email]))->toBe(Password::RESET_LINK_SENT);

    $message = assertCorreoOutlook($user->email, 'Restablecer contraseña – 4°TOP');
    expect($message->getHtmlBody())->toContain('Ana &amp; Luis', '37 minutos');
    $document = new DOMDocument;
    $document->loadHTML($message->getHtmlBody(), LIBXML_NOERROR | LIBXML_NOWARNING);
    $url = (new DOMXPath($document))->evaluate('string(//a[contains(@href, "/reset-password/")]/@href)');
    $token = basename((string) parse_url($url, PHP_URL_PATH));
    parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

    expect($query)->toBe(['email' => $user->email])
        ->and(Password::tokenExists($user, $token))->toBeTrue()
        ->and($message->getTextBody())->toContain($url, '37 minutos');
});

test('la migración versiona las plantillas vigentes y reactiva exactamente la versión previa al revertir', function () {
    $type = NotificationType::query()->create(['code' => 'visita_carcel.revision_pp', 'name' => 'Revisión PP', 'channel' => 'mail']);
    $legacy = ['subject' => 'Asunto vigente - {{ fecha }}', 'body_html' => '<p>Formato anterior</p>'];
    $old = $type->templates()->create([...$legacy, 'version' => 2, 'is_active' => true]);
    $inactive = $type->templates()->create([...$legacy, 'version' => 3, 'is_active' => false]);
    $empty = NotificationType::query()->create(['code' => 'insumo.stock_bajo', 'name' => 'Stock', 'channel' => 'mail']);
    $migration = require database_path('migrations/2026_11_25_134727_activate_outlook_notification_templates.php');

    $migration->up();
    $migration->up();

    $outlook = $type->templates()->where('is_active', true)->sole();
    expect($outlook->version)->toBe(4)
        ->and($outlook->subject)->toBe($legacy['subject'])
        ->and($outlook->body_html)->toContain('PODER JUDICIAL · REPÚBLICA DE CHILE')
        ->and($outlook->body_text)->toContain('Correo automático del sistema 4°TOP')
        ->and($old->fresh()->is_active)->toBeFalse()
        ->and($inactive->fresh()->is_active)->toBeFalse()
        ->and($type->templates()->count())->toBe(3)
        ->and($empty->templates()->where('is_active', true)->sole()->version)->toBe(1)
        ->and(NotificationType::query()->where('code', 'ttypp.listado.diario')->exists())->toBeFalse();

    $recipient = funcionarioCorreoIntegracion('jefatura@example.test');
    $this->artisan('notificaciones:visita-carcel-revision-pp', ['fecha' => '2026-08-20'])->assertSuccessful();
    assertCorreoOutlook($recipient->email, 'Asunto vigente - 20/08/2026', 'visita_carcel.revision_pp');
    expect($type->templates()->where('is_active', true)->sole()->id)->toBe($outlook->id);

    $outlook->update(['body_html' => $legacy['body_html']]);
    OutlookEmailTemplates::ensureActive($type);
    expect($type->templates()->where('is_active', true)->sole()->version)->toBe(5);

    $migration->down();
    $migration->down();

    expect($type->templates()->where('is_active', true)->sole()->id)->toBe($old->id)
        ->and($old->fresh()->body_html)->toBe($legacy['body_html'])
        ->and($outlook->fresh())->toBeNull()
        ->and($empty->templates()->count())->toBe(0);

    $legacyStock = $empty->templates()->create([
        'version' => 1,
        'subject' => '{{ title }}',
        'body_html' => '<p>{{ line }}</p>',
        'is_active' => true,
    ]);

    expect($empty->templates()->where('is_active', true)->sole()->id)->toBe($legacyStock->id);
});

test('revertir elimina la plantilla Outlook creada por ensureActive después de migrar', function () {
    $migration = require database_path('migrations/2026_11_25_134727_activate_outlook_notification_templates.php');
    $migration->up();
    $type = NotificationType::query()->create(['code' => 'insumo.stock_bajo', 'name' => 'Stock', 'channel' => 'mail']);

    OutlookEmailTemplates::ensureActive($type);
    expect($type->templates()->count())->toBe(1);

    $migration->down();

    expect($type->templates()->count())->toBe(0);
});

test('la migración no duplica una plantilla Outlook previa y al revertir reactiva la versión antigua', function () {
    $type = NotificationType::query()->create(['code' => 'visita_carcel.revision_pp', 'name' => 'Revisión PP', 'channel' => 'mail']);
    $old = $type->templates()->create(['version' => 1, 'subject' => 'Antiguo', 'body_html' => '<p>Antiguo</p>', 'is_active' => false]);
    OutlookEmailTemplates::ensureActive($type);
    $type->templates()->where('version', 2)->update(['outlook_rollout_previous_template_id' => null]);
    $migration = require database_path('migrations/2026_11_25_134727_activate_outlook_notification_templates.php');

    $migration->up();

    expect($type->templates()->count())->toBe(2);

    $migration->down();

    expect($type->templates()->count())->toBe(1)
        ->and($type->templates()->where('is_active', true)->sole()->id)->toBe($old->id);
});

test('el notifier reemplaza una plantilla antigua reactivada con una nueva versión Outlook', function () {
    $type = NotificationType::query()->create(['code' => 'turno1.resumen_semanal', 'name' => 'Turno 1', 'channel' => 'mail']);
    $legacy = $type->templates()->create([
        'version' => 1,
        'subject' => 'Asunto vigente',
        'body_html' => '<p>Formato anterior</p>',
        'is_active' => true,
    ]);
    $type->templates()->create([
        ...OutlookEmailTemplates::forType($type->code),
        'version' => 2,
        'is_active' => false,
    ]);

    OutlookEmailTemplates::ensureActive($type);
    OutlookEmailTemplates::ensureActive($type);

    $active = $type->templates()->where('is_active', true)->sole();
    expect($active->version)->toBe(3)
        ->and($active->subject)->toBe($legacy->subject)
        ->and($active->body_html)->toContain('<!-- 4totop-outlook-v1 -->')
        ->and($legacy->fresh()->is_active)->toBeFalse()
        ->and($legacy->fresh()->body_html)->toBe('<p>Formato anterior</p>')
        ->and($type->templates()->count())->toBe(3);
});

test('las rutas de preview de notificaciones devuelven 404', function (string $path) {
    $this->get('/preview/notificaciones-v2/'.$path)->assertNotFound();
    expect(Route::has('preview.notifications-v2.'.$path))->toBeFalse()
        ->and(Route::has('preview.progra'))->toBeTrue();
})->with(['demo', 'ttypp-listado', 'turno-asignado', 'turno2-acd', 'ausentismo-resumen', 'user-status', 'bitacora-pruebas']);
