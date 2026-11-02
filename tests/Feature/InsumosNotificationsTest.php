<?php

use App\Livewire\InsumosNotificacionesPage;
use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoExistencia;
use App\Models\InsumoMovimiento;
use App\Models\Sector;
use App\Models\User;
use App\Notifications\InsumosServiciosNotification;
use App\NotificationsV2\Application\Services\InsumosNotifier;
use App\NotificationsV2\Domain\Models\NotificationDispatch;
use App\NotificationsV2\Domain\Models\NotificationRecipient;
use App\Services\InsumoGestionService;
use App\Services\InsumoSolicitudService;
use Database\Seeders\ServiciosRoleSeeder;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Ramsey\Uuid\Uuid;

beforeEach(function () {
    $this->seed(ServiciosRoleSeeder::class);
    config()->set('notifications_v2.enabled', true);
    Queue::fake();
});

function createNotifiableInsumo(string $codigo, int $stockMinimo = 15): Insumo
{
    return Insumo::create([
        'codigo' => $codigo,
        'nombre' => "Insumo {$codigo}",
        'unidad_medida' => 'UN',
        'stock_minimo' => $stockMinimo,
    ]);
}

test('a request notifies only enabled servicios users and deduplicates its mail dispatch', function () {
    $servicios = User::factory()->create(['email' => 'servicios@example.cl']);
    $servicios->assignRole('servicios');
    $suspendedServicios = User::factory()->create(['suspended_at' => now(), 'email' => 'suspendido@example.cl']);
    $suspendedServicios->assignRole('servicios');
    $solicitante = User::factory()->create();
    $insumo = createNotifiableInsumo('NOT-001');

    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $insumo->id, 'cantidad' => 2]], $solicitante->id, null, 'Sala 701');
    app(InsumosNotifier::class)->solicitudCreada($solicitud);

    $dispatch = NotificationDispatch::query()->where('event_key', "insumo.solicitado.{$solicitud->id}")->sole();

    expect(NotificationDispatch::query()->where('event_key', "insumo.solicitado.{$solicitud->id}")->count())->toBe(1)
        ->and($servicios->fresh()->notifications()->where('type', InsumosServiciosNotification::class)->count())->toBe(1)
        ->and($suspendedServicios->fresh()->notifications()->count())->toBe(0)
        ->and(NotificationRecipient::query()->where('dispatch_id', $dispatch->id)->pluck('email')->all())->toBe(['servicios@example.cl'])
        ->and($dispatch->payload['firma_text'])->toBe('Sala 701')
        ->and($dispatch->payload['firma_html'])->toContain('Sala 701');
});

test('assignment notifies servicios, emails the recipient, and alerts once when stock crosses its minimum', function () {
    $servicios = User::factory()->create(['email' => 'servicios@example.cl']);
    $servicios->assignRole('servicios');
    $actor = User::factory()->create();
    $recipient = User::factory()->create(['email' => 'funcionario@example.cl']);
    $sector = Sector::factory()->create();
    $insumo = createNotifiableInsumo('NOT-002', 5);
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 6]);
    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $insumo->id, 'cantidad' => 3]], $recipient->id);
    $detalle = $solicitud->detalles->sole();
    $gestion = app(InsumoGestionService::class);

    $gestion->entregar($solicitud, [['solicitud_detalle_id' => $detalle->id, 'sector_id' => $sector->id, 'cantidad' => 2]], $actor->id);
    $gestion->entregar($solicitud, [['solicitud_detalle_id' => $detalle->id, 'sector_id' => $sector->id, 'cantidad' => 1]], $actor->id);

    expect(NotificationDispatch::query()->where('event_key', 'like', 'insumo.asignado.%')->count())->toBe(2)
        ->and(NotificationDispatch::query()->where('event_key', 'like', 'insumo.stock_bajo.%')->count())->toBe(1)
        ->and(NotificationRecipient::query()->where('email', 'funcionario@example.cl')->count())->toBe(2)
        ->and($servicios->fresh()->notifications()->where('type', InsumosServiciosNotification::class)->count())->toBe(4);
});

test('direct assignment emails the selected funcionario without a user account', function () {
    $servicios = User::factory()->create(['email' => 'servicios@example.cl']);
    $servicios->assignRole('servicios');
    $actor = User::factory()->create();
    $funcionario = Funcionario::create([
        'nombre' => 'Funcionario de catalogo', 'rol' => 'informatico', 'email' => 'catalogo@example.cl', 'fecha_cumpleanos' => '1990-01-01',
    ]);
    $sector = Sector::factory()->create();
    $insumo = createNotifiableInsumo('NOT-003');
    InsumoExistencia::create(['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 2]);

    app(InsumoGestionService::class)->asignarDirecto([
        ['insumo_id' => $insumo->id, 'sector_id' => $sector->id, 'cantidad' => 1],
    ], $actor->id, $funcionario->id);

    expect(NotificationRecipient::query()->where('email', 'catalogo@example.cl')->count())->toBe(0)
        ->and(NotificationRecipient::query()->where('email', 'servicios@example.cl')->count())->toBe(1)
        ->and(InsumoMovimiento::query()->sole()->asignado_a_funcionario_id)->toBe($funcionario->id)
        ->and(InsumoMovimiento::query()->sole()->asignado_a_user_id)->toBeNull();
});

test('services user can mark only their own insumos notifications as read', function () {
    $user = User::factory()->create();
    $user->notify(new InsumosServiciosNotification('insumo.test.1', 'Aviso', 'Detalle', '/insumos/gestion'));
    $notification = $user->fresh()->notifications()->sole();

    Livewire::actingAs($user)
        ->test(InsumosNotificacionesPage::class)
        ->assertSee('Aviso')
        ->call('marcarLeida', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('servicios users without valid email receive database notifications but not mail', function () {
    $servicios = User::factory()->create(['email' => 'correo-invalido']);
    $servicios->assignRole('servicios');
    $valid = User::factory()->create(['email' => 'servicios@example.cl']);
    $valid->assignRole('servicios');
    $insumo = createNotifiableInsumo('NOT-004');
    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $insumo->id, 'cantidad' => 1]], User::factory()->create()->id);

    expect($servicios->notifications()->count())->toBe(1)
        ->and(NotificationRecipient::query()->where('email', 'correo-invalido')->count())->toBe(0)
        ->and(NotificationRecipient::query()->where('email', 'servicios@example.cl')->count())->toBe(1);
});

test('a deterministic notification id prevents duplicate inserts after the existence check', function () {
    $user = User::factory()->create();
    $eventKey = 'insumo.test.carrera';
    $id = Uuid::uuid5(Uuid::NAMESPACE_URL, "4totop:{$user->getKey()}|{$eventKey}")->toString();
    $notification = new InsumosServiciosNotification('otro-evento', 'Aviso', 'Detalle', '/insumos/gestion');
    $notification->id = $id;
    $user->notify($notification);

    $method = new ReflectionMethod(InsumosNotifier::class, 'notifyDatabase');
    $method->invoke(app(InsumosNotifier::class), collect([$user]), $eventKey, 'Aviso', 'Detalle', '/insumos/gestion');

    expect($user->notifications()->count())->toBe(1);
});
