<?php

use App\Livewire\InsumosReportesPage;
use App\Models\Insumo;
use App\Models\InsumoMovimiento;
use App\Models\InsumoSolicitud;
use App\Models\InsumoSolicitudDetalle;
use App\Models\Sector;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Str;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('report groups and filters assignment movements', function () {
    $operator = User::factory()->create();
    $operator->givePermissionTo('insumos.reportes');
    $recipient = User::factory()->create(['name' => 'Persona asignada']);
    $otherRecipient = User::factory()->create(['name' => 'Otra persona']);
    $insumo = Insumo::create(['codigo' => 'REP-001', 'nombre' => 'Papel carta', 'unidad_medida' => 'UN']);
    $otherInsumo = Insumo::create(['codigo' => 'REP-002', 'nombre' => 'Toner', 'unidad_medida' => 'UN']);
    $sector = Sector::factory()->create(['nombre' => 'Bodega reportes']);

    foreach ([[$insumo, $recipient, 4], [$insumo, $recipient, 6], [$otherInsumo, $otherRecipient, 3]] as [$item, $user, $cantidad]) {
        InsumoMovimiento::create([
            'operacion_uuid' => (string) Str::uuid(), 'insumo_id' => $item->id, 'sector_id' => $sector->id,
            'tipo' => 'asignacion', 'cantidad' => $cantidad, 'stock_anterior' => 20, 'stock_posterior' => 20 - $cantidad,
            'asignado_a_user_id' => $user->id, 'created_by_user_id' => $operator->id,
        ]);
    }

    $solicitud = InsumoSolicitud::create(['solicitante_user_id' => $recipient->id, 'estado' => 'parcial']);
    $detalle = InsumoSolicitudDetalle::create([
        'insumo_solicitud_id' => $solicitud->id,
        'insumo_id' => $insumo->id,
        'cantidad_solicitada' => 12,
    ]);
    InsumoMovimiento::query()
        ->where('insumo_id', $insumo->id)
        ->where('asignado_a_user_id', $recipient->id)
        ->update(['solicitud_detalle_id' => $detalle->id]);

    Livewire::actingAs($operator)
        ->test(InsumosReportesPage::class)
        ->assertSee('Persona asignada')
        ->assertSee('10')
        ->assertSee('12')
        ->assertSee('2')
        ->set('insumoFiltro', (string) $insumo->id)
        ->assertSee('Papel carta')
        ->assertDontSeeHtml('<td class="p-2">Toner</td>')
        ->assertSee('10');
});

test('report filters assignments by recipient and date', function () {
    $operator = User::factory()->create();
    $operator->givePermissionTo('insumos.reportes');
    $recipient = User::factory()->create(['name' => 'Destinatario reciente']);
    $otherRecipient = User::factory()->create(['name' => 'Destinatario anterior']);
    $insumo = Insumo::create(['codigo' => 'REP-003', 'nombre' => 'Lapiz', 'unidad_medida' => 'UN']);
    $sector = Sector::factory()->create();

    foreach ([[$recipient, now()], [$otherRecipient, now()->subDays(2)]] as [$user, $fecha]) {
        InsumoMovimiento::create([
            'operacion_uuid' => (string) Str::uuid(), 'insumo_id' => $insumo->id, 'sector_id' => $sector->id,
            'tipo' => 'asignacion', 'cantidad' => 1, 'stock_anterior' => 2, 'stock_posterior' => 1,
            'asignado_a_user_id' => $user->id, 'created_by_user_id' => $operator->id, 'created_at' => $fecha, 'updated_at' => $fecha,
        ]);
    }

    Livewire::actingAs($operator)
        ->test(InsumosReportesPage::class)
        ->set('usuarioFiltro', (string) $recipient->id)
        ->assertSeeHtml('<td class="p-2">Destinatario reciente</td>')
        ->assertDontSeeHtml('<td class="p-2">Destinatario anterior</td>')
        ->set('fechaHasta', now()->subDay()->toDateString())
        ->assertDontSeeHtml('<td class="p-2">Destinatario reciente</td>');
});

test('report is forbidden without permission', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(InsumosReportesPage::class)
        ->assertForbidden();
});
