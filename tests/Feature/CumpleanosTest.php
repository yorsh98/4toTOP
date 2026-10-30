<?php

use App\Models\Funcionario;
use App\NotificationsV2\Application\Services\BirthdayNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\Services\FrontOfficeCatalog;
use App\Services\FuncionarioCatalog;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function birthdayPerson(string $name, string $email, string $date): Funcionario
{
    return Funcionario::create(['nombre' => $name, 'email' => $email, 'fecha_cumpleanos' => $date, 'rol' => 'informatico']);
}

test('la vista publica protege datos y ordena los cumpleaños', function () {
    Carbon::setTestNow(Carbon::parse('2025-02-28 12:00', 'America/Santiago'));
    birthdayPerson('Segundo', 'segundo@example.test', '1980-03-15');
    birthdayPerson('Bisiesto', 'bisiesto@example.test', '1984-02-29');
    birthdayPerson('Eliminado', 'eliminado@example.test', '1980-01-01')->delete();
    $response = $this->get(route('cumpleanos.index'))->assertSuccessful()->assertSee('NOMBRE')->assertSee('FECHA')->assertDontSee('ÁREA')->assertSee('class="today"', false)->assertSee(route('welcome'))->assertDontSee('1984')->assertDontSee('bisiesto@example.test')->assertDontSee('Eliminado')->assertSeeInOrder(['Bisiesto', 'Segundo']);
    expect($response->getContent())->toContain('28 de febrero');
    Carbon::setTestNow();
});

test('la portada enlaza a cumpleaños como acceso interno', function () {
    $item = collect(app(FrontOfficeCatalog::class)->items())->firstWhere('key', 'cumpleanos');
    expect($item['href'])->toBe(route('cumpleanos.index'))->and($item['external'] ?? false)->toBeFalse();
    $this->get(route('welcome'))->assertDontSee('10.13.214.129');
});

test('prepara saludos individuales y recordatorios con proximos entre años', function () {
    birthdayPerson('Hoy Uno', 'uno@example.test', '1980-12-31');
    birthdayPerson('Hoy Dos', 'dos@example.test', '1981-12-31');
    birthdayPerson('Mañana', 'manana@example.test', '1980-01-01');
    birthdayPerson('Fuera', 'fuera@example.test', '1980-01-06');
    birthdayPerson('Inválido', 'no-es-correo', '1980-01-01');
    $messages = app(BirthdayNotifier::class)->notify(Carbon::parse('2025-12-31', 'America/Santiago'), true);
    expect($messages)->toHaveCount(4);
    $greeting = collect($messages)->firstWhere('email', 'uno@example.test');
    $reminder = collect($messages)->firstWhere('email', 'manana@example.test');
    expect($greeting['type'])->toBe('saludo')->and($greeting['html'])->toContain('Hoy Dos')->not->toContain('Hoy Uno</strong>, su mail');
    expect($reminder['type'])->toBe('recordatorio')->and($reminder['html'])->toContain('Hoy Uno', 'Hoy Dos', 'Próximos cumpleaños', '01 de enero')->not->toContain('Fuera', 'Zonal');
});

test('no envia sin cumpleaños y dry run no marca idempotencia', function () {
    config(['notifications_v2.enabled' => false]);
    birthdayPerson('Hoy', 'hoy@example.test', '1980-03-01');
    birthdayPerson('Otro', 'otro@example.test', '1980-03-02');
    Event::fake([MessageSent::class]);
    Cache::flush();
    $notifier = app(BirthdayNotifier::class);
    expect($notifier->notify(Carbon::parse('2025-02-28', 'America/Santiago'), true))->toBe([]);
    $date = Carbon::parse('2025-03-01', 'America/Santiago');
    $notifier->notify($date, true);
    Event::assertNotDispatched(MessageSent::class);
    $notifier->notify($date);
    $notifier->notify($date);
    Event::assertDispatchedTimes(MessageSent::class, 2);
});

test('el comando tiene programación diaria a las ocho', function () {
    $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains($event->command ?? '', 'notificaciones:cumpleanos'));
    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 8 * * *');
});

test('la idempotencia del orquestador es por destinatario', function () {
    config(['notifications_v2.enabled' => true]);
    birthdayPerson('Hoy', 'hoy@example.test', '1980-03-01');
    birthdayPerson('Otro', 'otro@example.test', '1980-04-01');
    Queue::fake();
    $notifier = app(BirthdayNotifier::class);
    $date = Carbon::parse('2025-03-01', 'America/Santiago');
    $notifier->notify($date);
    $notifier->notify($date);
    expect(NotificationDispatch::query()->where('event_key', 'like', 'cumpleanos.%')->count())->toBe(2);
});

test('el comando dry run no envia', function () {
    config(['notifications_v2.enabled' => false]);
    birthdayPerson('Hoy', 'hoy@example.test', '1980-03-01');
    Mail::fake();
    $this->artisan('notificaciones:cumpleanos 2025-03-01 --dry-run')->assertSuccessful();
    Mail::assertNothingOutgoing();
});

test('el catálogo diario acepta strings y mantiene orden y fechas bisiestas', function () {
    birthdayPerson('Zeta', 'zeta@example.test', '1984-02-29');
    birthdayPerson('Alfa', 'alfa@example.test', '1990-02-28');
    $catalog = app(FuncionarioCatalog::class);
    expect(array_column($catalog->cumpleanerosDelDia('2025-02-28'), 'nombre'))->toBe(['Alfa', 'Zeta'])
        ->and(array_column($catalog->cumpleanerosDelDia(Carbon::parse('2024-02-28 00:00', 'America/Santiago')), 'nombre'))->toBe(['Alfa'])
        ->and(array_column($catalog->cumpleanerosDelDia('2024-02-29'), 'nombre'))->toBe(['Zeta']);
});

test('la vista destaca hoy y ordena por calendario sin exponer datos personales', function () {
    Carbon::setTestNow(Carbon::parse('2025-03-05 12:00', 'America/Santiago'));
    birthdayPerson('Temprano', 'temprano@example.test', '2000-03-05');
    birthdayPerson('Tardío', 'tardio@example.test', '1970-03-20');
    birthdayPerson('Borrado', 'borrado@example.test', '1990-03-06')->delete();
    $html = $this->get(route('cumpleanos.index'))->assertSuccessful()->assertSeeInOrder(['Temprano', 'Tardío'])->getContent();
    expect($html)->toContain('<tr class="today"><td>Temprano</td><td>05 de marzo</td></tr>', route('welcome'), 'favicon.webp')
        ->not->toContain('2000', '1970', 'example.test', 'Borrado');
    preg_match_all('/<th\b[^>]*>(.*?)<\/th>/s', $html, $headers);
    expect($headers[1])->toBe(['NOMBRE', 'FECHA']);
    Carbon::setTestNow();
});

test('las cuatro variantes de correo tienen destinatarios y asuntos correctos', function (bool $multiple) {
    birthdayPerson('Celebrado', 'celebrado@example.test', '1980-03-05');
    if ($multiple) {
        birthdayPerson('Compañero', 'companero@example.test', '1981-03-05');
    }
    birthdayPerson('Receptor', 'receptor@example.test', '1980-04-01');
    birthdayPerson('Inválido', 'correo-invalido', '1980-04-03');
    birthdayPerson('Eliminado', 'eliminado@example.test', '1980-03-05')->delete();
    $messages = collect(app(BirthdayNotifier::class)->notify(Carbon::parse('2025-03-05', 'America/Santiago'), true));
    expect($messages)->toHaveCount($multiple ? 3 : 2);
    $greeting = $messages->firstWhere('email', 'celebrado@example.test');
    $reminder = $messages->firstWhere('email', 'receptor@example.test');
    expect($greeting['type'])->toBe('saludo')->and($greeting['subject'])->toBe('Te deseamos un Feliz Cumpleaños')
        ->and($greeting['html'])->toContain('Feliz cumpleaños Celebrado')
        ->and($reminder['type'])->toBe('recordatorio')->and($reminder['subject'])->toBe('Deséales un feliz Cumpleaños')
        ->and($reminder['html'])->toContain('Celebrado');
    if ($multiple) {
        expect($greeting['html'])->toContain('Compañero')->and($reminder['html'])->toContain('Compañero');
    }
    foreach ($messages as $message) {
        expect($message['html'])->not->toContain('Zonal', 'Zonalino', 'CAPJ', '10.13.', 'Eliminado');
    }
})->with([false, true]);

test('los próximos abarcan cinco días y cruzan año y fin de mes sin correos', function (string $date, string $tomorrow, string $fifth, string $sixth) {
    birthdayPerson('Hoy', 'hoy@example.test', $date);
    birthdayPerson('Mañana', 'manana@example.test', $tomorrow);
    birthdayPerson('Quinto', 'quinto@example.test', $fifth);
    birthdayPerson('Sexto', 'sexto@example.test', $sixth);
    $messages = app(BirthdayNotifier::class)->notify(Carbon::parse($date, 'America/Santiago'), true);
    $html = collect($messages)->firstWhere('email', 'manana@example.test')['html'];
    $upcoming = explode('Próximos cumpleaños', $html)[1];
    expect($upcoming)->toContain('Mañana', 'Quinto')->not->toContain('Hoy', 'Sexto', '@');
})->with([
    ['2025-12-31', '2000-01-01', '2000-01-05', '2000-01-06'],
    ['2025-04-28', '2000-04-29', '2000-05-03', '2000-05-04'],
]);

test('dry run e idempotencia funcionan en ambos modos y por fecha', function (bool $enabled) {
    config(['notifications_v2.enabled' => $enabled, 'mail.default' => 'array', 'notifications_v2.mailer' => 'array']);
    Cache::flush();
    Queue::fake();
    birthdayPerson('Hoy', 'hoy@example.test', '1980-03-01');
    birthdayPerson('Otro', 'otro@example.test', '1980-03-02');
    Event::fake([MessageSent::class]);
    $this->artisan('notificaciones:cumpleanos 2025-03-01 --dry-run')->assertSuccessful();
    Event::assertNotDispatched(MessageSent::class);
    expect(NotificationDispatch::query()->count())->toBe(0);
    $notifier = app(BirthdayNotifier::class);
    $notifier->notify(Carbon::parse('2025-03-01', 'America/Santiago'));
    $notifier->notify(Carbon::parse('2025-03-01', 'America/Santiago'));
    $notifier->notify(Carbon::parse('2025-03-02', 'America/Santiago'));
    if ($enabled) {
        expect(NotificationDispatch::query()->count())->toBe(4);
    } else {
        Event::assertDispatchedTimes(MessageSent::class, 4);
    }
})->with([false, true]);

test('el comando rechaza fechas inválidas', function () {
    Mail::fake();
    $this->artisan('notificaciones:cumpleanos 2025-02-30')->assertFailed();
    Mail::assertNothingOutgoing();
});

test('el correo enviado incrusta la imagen y usa el remitente del tribunal', function () {
    config(['notifications_v2.enabled' => false, 'mail.default' => 'array']);
    Cache::flush();
    birthdayPerson('Hoy', 'hoy@example.test', '1980-03-05');
    $sent = [];
    Event::listen(MessageSent::class, function (MessageSent $event) use (&$sent) {
        $sent[] = $event->message;
    });
    app(BirthdayNotifier::class)->notify(Carbon::parse('2025-03-05', 'America/Santiago'));
    expect($sent)->toHaveCount(1);
    expect($sent[0]->getHtmlBody())->toContain('cid:')->not->toContain(asset('img/cumpleanos/cumpleanos.png'));
    expect($sent[0]->getFrom()[0]->getName())->toBe('4º Tribunal de Juicio Oral en lo Penal de Santiago');
});

test('un fallo de destinatario no interrumpe los demás', function () {
    config(['notifications_v2.enabled' => false, 'mail.default' => 'array']);
    Cache::flush();
    birthdayPerson('Primero', 'primero@example.test', '1980-03-05');
    birthdayPerson('Segundo', 'segundo@example.test', '1980-04-05');
    $calls = 0;
    Mail::shouldReceive('send')->twice()->andReturnUsing(function () use (&$calls) {
        if (++$calls === 1) {
            throw new RuntimeException('fallo ficticio');
        }
    });
    app(BirthdayNotifier::class)->notify(Carbon::parse('2025-03-05', 'America/Santiago'));
    expect($calls)->toBe(2);
});
