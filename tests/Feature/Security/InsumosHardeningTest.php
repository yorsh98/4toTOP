<?php

use App\Livewire\InsumosGestionPage;
use App\Livewire\InsumosImportacionesPage;
use App\Livewire\InsumosInventarioPage;
use App\Livewire\InsumosReportesPage;
use App\Livewire\InsumosSolicitudesPage;
use App\Models\Funcionario;
use App\Models\Insumo;
use App\Models\InsumoSolicitud;
use App\Models\Sector;
use App\Models\User;
use App\Services\InsumoSolicitudService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xls;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->user = User::factory()->create();
    $this->user->assignRole('SuperAdmin');
    $this->insumo = Insumo::create(['codigo' => 'SEC-1', 'nombre' => 'Papel', 'unidad_medida' => 'UN']);
});

it('protects server selected inventory records while allowing normal selection', function () {
    $component = Livewire::actingAs($this->user)->test(InsumosInventarioPage::class);
    expect(fn () => $component->set('editingId', $this->insumo->id))->toThrow(Exception::class, 'Cannot update locked property');
    expect(fn () => $component->set('reposicionInsumoId', $this->insumo->id))->toThrow(Exception::class, 'Cannot update locked property');
    $component->call('editar', $this->insumo->id)->set('nombre', 'Papel nuevo')->call('guardar')->assertHasNoErrors();
    $component->call('abrirReposicion', $this->insumo->id)->set('reposicionSectorId', Sector::factory()->create()->id)->call('registrarReposicion')->assertHasNoErrors();
    expect($this->insumo->fresh()->nombre)->toBe('Papel nuevo');
});

it('protects the import preview from client updates', function () {
    $component = Livewire::actingAs($this->user)->test(InsumosImportacionesPage::class);
    expect(fn () => $component->set('preview', ['archivo_hash' => 'forged']))->toThrow(Exception::class, 'Cannot update locked property');
    $component->assertSet('preview', null);
});

it('protects management selection and cart while allowing server actions', function () {
    $sector = Sector::factory()->create();
    $this->insumo->existencias()->create(['sector_id' => $sector->id, 'cantidad' => 5]);
    $recipient = Funcionario::create(['nombre' => 'Funcionario', 'rol' => 'informatico', 'email' => 'destino@example.cl', 'fecha_cumpleanos' => '1990-01-01']);
    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $this->insumo->id, 'cantidad' => 2]], $this->user->id);
    $component = Livewire::actingAs($this->user)->test(InsumosGestionPage::class);
    expect(fn () => $component->set('selectedSolicitudId', $solicitud->id))->toThrow(Exception::class, 'Cannot update locked property');
    expect(fn () => $component->set('directCart', [['insumo_id' => $this->insumo->id, 'cantidad' => 1]]))->toThrow(Exception::class, 'Cannot update locked property');
    $component->call('seleccionarSolicitud', $solicitud->id)->assertSet('selectedSolicitudId', $solicitud->id)
        ->set('directInsumoId', $this->insumo->id)->call('agregarAsignacionDirecta')->assertHasNoErrors()
        ->assertSet("directCart.{$this->insumo->id}.cantidad", 1)
        ->set('directRecipientId', $recipient->id)->call('asignarDirecto')->assertHasNoErrors()->assertSet('directCart', []);
});

it('validates delivery quantities before casting and accepts a valid delivery', function () {
    $sector = Sector::factory()->create();
    $this->insumo->existencias()->create(['sector_id' => $sector->id, 'cantidad' => 5]);
    $solicitud = app(InsumoSolicitudService::class)->crear([['insumo_id' => $this->insumo->id, 'cantidad' => 2]], $this->user->id);
    $detalle = $solicitud->detalles()->firstOrFail();
    $component = Livewire::actingAs($this->user)->test(InsumosGestionPage::class)->call('seleccionarSolicitud', $solicitud->id);
    $component->set("entregas.{$detalle->id}.cantidad", 1.5)->call('entregar')->assertHasErrors("entregas.{$detalle->id}.cantidad");
    $component->set("entregas.{$detalle->id}.cantidad", 2)->call('entregar')->assertHasNoErrors();
    expect($solicitud->fresh()->estado)->toBe('completada');
});

it('rejects manipulated cart quantities and keys and accepts a valid request', function () {
    $component = Livewire::actingAs($this->user)->test(InsumosSolicitudesPage::class);
    $component->set('cart', [$this->insumo->id => 1.5])->call('solicitar')->assertHasErrors();
    $component->set('cart', [999999 => 1])->call('solicitar')->assertHasErrors();
    $component->set('cart', [$this->insumo->id => 2])->call('solicitar')->assertHasNoErrors();
    expect(InsumoSolicitud::query()->where('solicitante_user_id', $this->user->id)->count())->toBe(1);
});

it('ignores invalid report filters without breaking valid filters', function () {
    Livewire::actingAs($this->user)->test(InsumosReportesPage::class)
        ->set('fechaDesde', '2026-99-99')->set('fechaHasta', 'invalid')
        ->set('fechaSolicitudDesde', 'bad')->set('fechaSolicitudHasta', '2026-02-30')
        ->set('insumoFiltro', 'invalid')->set('usuarioFiltro', 'funcionario:abc')->assertOk()
        ->set('insumoFiltro', (string) $this->insumo->id)->set('fechaDesde', '2026-01-01')->assertOk();
});

it('previews and imports a real spreadsheet after confirmation', function () {
    Storage::fake('local');
    Sector::factory()->create(['identificador_mapa' => '818']);
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setCellValue('A1', 'TODAS LAS BODEGAS');
    $sheet->fromArray(['Bodega', 'Codigo', 'Nombre Articulo', 'UME', 'Stock Actual', '', '', '', '', '', '', '', '', 'UBICACIÓN'], null, 'A12');
    $sheet->fromArray([1, 'SEC-1', 'Papel', 'UN', 5, '', '', '', '', '', '', '', '', 'BODEGA 818'], null, 'A13');
    $sheet->setCellValue('A15', 'TOTAL STOCK ACTUAL');
    $path = tempnam(sys_get_temp_dir(), 'stock-test-');
    (new Xls($spreadsheet))->save($path);
    $spreadsheet->disconnectWorksheets();

    try {
        $file = UploadedFile::fake()->createWithContent('stock.xls', file_get_contents($path));
        Livewire::actingAs($this->user)->test(InsumosImportacionesPage::class)
            ->set('archivo', $file)->call('previsualizar')->assertHasNoErrors()
            ->set('confirmarCambios', true)->call('confirmar')->assertHasNoErrors()->assertSet('preview', null);
        expect(Storage::disk('local')->allFiles('insumos/importaciones'))->toHaveCount(1);
    } finally {
        @unlink($path);
    }
});
