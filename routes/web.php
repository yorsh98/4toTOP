<?php

use App\Http\Controllers\accController;
// Seccion de use de MAIL
use App\Http\Controllers\addfuncionarioController;
use App\Http\Controllers\AdmAudvController;
//

// Controllers
use App\Http\Controllers\AudienciasExportController;
use App\Http\Controllers\ausenciasController;
use App\Http\Controllers\ausentismoController;
use App\Http\Controllers\buscadorsentenciasController;
use App\Http\Controllers\CumpleanosController;
use App\Http\Controllers\docController;
use App\Http\Controllers\DownloadConversionController;
use App\Http\Controllers\guiasController;
use App\Http\Controllers\guiastelefonicasController;
use App\Http\Controllers\libertadController;
use App\Http\Controllers\migraunijudController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\oficiolibertadController;
use App\Http\Controllers\procController;
use App\Http\Controllers\prograController;
use App\Http\Controllers\PrograMailController;
use App\Http\Controllers\ServiciosInventarioExportController;
use App\Http\Controllers\SistOficioLibertadesController;
use App\Http\Controllers\tabla2Controller;
use App\Http\Controllers\tablasController;
use App\Http\Controllers\TtyppExportController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\VisitaCarcelExportController;
use App\Http\Controllers\VisitaCarcelPenalesExportController;
use App\Livewire\AudiosSalasPage;
use App\Livewire\BackofficeFileConverterPage;
use App\Livewire\BitacoraPruebasPage;
use App\Livewire\BitacoraPruebasPublicPage;
use App\Livewire\DocumentosBackofficePage;
use App\Livewire\FileConversionHistoryPage;
use App\Livewire\FuncionariosPage;
use App\Livewire\InsumosGestionPage;
use App\Livewire\InsumosImportacionesPage;
use App\Livewire\InsumosInventarioPage;
use App\Livewire\InsumosNotificacionesPage;
use App\Livewire\InsumosReportesPage;
use App\Livewire\InsumosSolicitudesPage;
use App\Livewire\PublicFileConverterPage;
use App\Livewire\Security\AuditPage;
use App\Livewire\Security\RolePermissionsPage;
use App\Livewire\Security\RolesPage;
use App\Livewire\Security\UsersRolesPage;
use App\Livewire\ServiciosInventarioPage;
use App\Livewire\ServiciosMapaPage;
use App\Livewire\ServiciosMapaPublicPage;
use App\Livewire\TtyppPage;
use App\Livewire\TurnoPage;
use App\Livewire\VisitaCarcelPage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
| Elige una sola raíz para evitar colisiones.
| Dejamos la vista "welcome" en "/" y movemos enviarTurno a otra ruta.
*/
// Home principal: envia la informacion de turnos a la vista.
Route::get('/', [TurnoController::class, 'enviarTurno'])->name('welcome');
Route::get('/convertir-archivos', PublicFileConverterPage::class)->name('conversions.public');
Route::get('/conversiones/descargar/{conversion:token}', DownloadConversionController::class)->name('conversions.download');
Route::get('/backoffice/convertir-archivos', BackofficeFileConverterPage::class)
    ->middleware(['auth', 'can:navbar.conversiones.visible'])->name('conversions.backoffice');
Route::get('/backoffice/convertir-archivos/registro', FileConversionHistoryPage::class)
    ->middleware(['auth', 'can:conversiones.ver_registro'])->name('conversions.history');

/*
|--------------------------------------------------------------------------
| SISTEMA OFICIO/LIBERTADES
|--------------------------------------------------------------------------
| Agrupado con prefix y name para evitar nombres duplicados.
*/
Route::prefix('SistOficioLibertades')->name('SistOficioLibertades.')->group(function () {
    Route::get('/', [SistOficioLibertadesController::class, 'index'])->name('index');
    Route::get('/create', [SistOficioLibertadesController::class, 'create'])->name('create');
    Route::post('/', [SistOficioLibertadesController::class, 'store'])->name('store');
    // Ruta auxiliar para inyectar solicitantes (antes colisionaba con .index)
    Route::get('/solicitantes', [addfuncionarioController::class, 'enviarSolicitantes'])->name('solicitantes');
});

// Alias legacy para “/oficios” → redirige al índice (sin duplicar nombre)
Route::redirect('/oficios', '/SistOficioLibertades');

/*
|--------------------------------------------------------------------------
| ADD FUNCIONARIO (con auth)
|--------------------------------------------------------------------------
*/
Route::prefix('addfuncionario')->name('addfuncionario.')->middleware(['auth'])->group(function () {
    Route::get('/', FuncionariosPage::class)->name('index')->middleware('can:navbar.funcionarios.visible');
});

/*
|--------------------------------------------------------------------------
| OFICIO / LIBERTAD (modulos antiguos)
|--------------------------------------------------------------------------
*/
Route::get('/oficiolibertad', [oficiolibertadController::class, 'index'])->name('oficiolibertad')->middleware(['auth', 'can:navbar.oficios_libertades.visible']);
// (Si usas libertadController, define aquí sus rutas con nombres únicos)

/*
|--------------------------------------------------------------------------
| TURNOS
|--------------------------------------------------------------------------
*/
Route::get('/turno', TurnoPage::class)->name('turno')->middleware(['auth', 'can:navbar.turnos.visible']);
Route::patch('/turno/{id}', [TurnoController::class, 'update'])
    ->name('turno.update')
    ->middleware(['auth', 'can:navbar.turnos.editable']);

/*
|--------------------------------------------------------------------------
| PÁGINAS VARIAS
|--------------------------------------------------------------------------
*/
Route::get('/tablas', [tablasController::class, 'index']);
Route::get('/tabla2', [tabla2Controller::class, 'index'])->name('tabla2');

Route::get('/guias', [guiasController::class, 'index'])->name('guias')->middleware(['auth', 'can:navbar.guias.visible']);
Route::get('/guiastelefonicas', [guiastelefonicasController::class, 'index'])->name('guiastelefonicas');

Route::get('/acc', [accController::class, 'index'])->name('acc');
Route::get('/doc', [docController::class, 'index'])->name('doc');
Route::get('/proc', [procController::class, 'index'])->name('proc');
Route::get('/migraunijud', [migraunijudController::class, 'index'])->name('migraunijud');
Route::get('/buscadorsentencias', [buscadorsentenciasController::class, 'index'])->name('buscadorsentencias');

/*
|--------------------------------------------------------------------------
| PROGRA (evitar doble /progra con mismo name)
|--------------------------------------------------------------------------
*/
Route::prefix('progra')->name('progra.')->group(function () {
    Route::get('/', [prograController::class, 'index'])->name('index');
});

/*
|--------------------------------------------------------------------------
| ENVÍO DE SOLICITUD (manteniendo tu configuración)
|--------------------------------------------------------------------------
*/
Route::post('/enviar-solicitud', [SistOficioLibertadesController::class, 'enviarSolicitud'])
    ->middleware(['auth', 'can:navbar.oficios_libertades.editable'])
    ->name('enviar-solicitud');

/*
|--------------------------------------------------------------------------
| LIVEWIRE PÚBLICO (según tu comentario original)
|--------------------------------------------------------------------------
*/
Route::get('/AdmAudv', [AdmAudvController::class, 'index'])->name('AdmAudv')->middleware(['auth', 'can:navbar.audiencias.visible']);
Route::get('/Monitor', [MonitorController::class, 'index'])->name('Monitor');
Route::get('/bitacora-pruebas', BitacoraPruebasPublicPage::class)->name('bitacora-pruebas.public');
Route::get('/servicios/mapa-publico', ServiciosMapaPublicPage::class)->name('servicios.mapa-publico');
Route::get('/backoffice/bitacora-pruebas', BitacoraPruebasPage::class)
    ->name('bitacora-pruebas.index')
    ->middleware(['auth', 'can:navbar.bitacora_pruebas.visible']);
Route::get('/backoffice/documentos', DocumentosBackofficePage::class)
    ->name('backoffice.documentos.index')
    ->middleware(['auth', 'can:navbar.documentos.visible']);
Route::get('/backoffice/ttypp', TtyppPage::class)
    ->name('ttypp.index')
    ->middleware(['auth', 'can:navbar.ttypp.visible']);
Route::get('/backoffice/ttypp/export', TtyppExportController::class)
    ->name('ttypp.export')
    ->middleware(['auth', 'can:ttypp.exportar']);
Route::get('/backoffice/servicios/inventario', ServiciosInventarioPage::class)
    ->name('backoffice.servicios.inventario')
    ->middleware(['auth', 'can:servicios.ver']);
Route::get('/backoffice/servicios/inventario/exportar', ServiciosInventarioExportController::class)
    ->name('backoffice.servicios.inventario.exportar')
    ->middleware(['auth', 'can:servicios.ver']);
Route::get('/backoffice/servicios/mapa', ServiciosMapaPage::class)
    ->name('backoffice.servicios.mapa')
    ->middleware(['auth', 'can:servicios.ver']);
Route::get('/insumos/solicitudes', InsumosSolicitudesPage::class)
    ->name('insumos.solicitudes')
    ->middleware('auth');
Route::get('/insumos/notificaciones', InsumosNotificacionesPage::class)
    ->name('insumos.notificaciones')
    ->middleware('auth');
Route::get('/insumos/gestion', InsumosGestionPage::class)
    ->name('insumos.gestion')
    ->middleware('auth');
Route::get('/insumos/inventario', InsumosInventarioPage::class)
    ->name('insumos.inventario')
    ->middleware('auth');
Route::get('/insumos/importaciones', InsumosImportacionesPage::class)
    ->name('insumos.importaciones')
    ->middleware('auth');
Route::get('/insumos/reportes', InsumosReportesPage::class)
    ->name('insumos.reportes')
    ->middleware('auth');
Route::get('/audios-salas', AudiosSalasPage::class)
    ->name('audios-salas.index')
    ->middleware(['auth', 'can:audios_salas.ver']);
Route::get('/visita-carcel', VisitaCarcelPage::class)
    ->name('visita-carcel.index')
    ->middleware(['auth', 'can:visita_carcel.ver']);
Route::get('/visita-carcel/exportar-por-penal', VisitaCarcelExportController::class)
    ->name('visita-carcel.exportar-por-penal')
    ->middleware(['auth', 'can:visita_carcel.gestionar']);
Route::get('/visita-carcel/exportar-penales', VisitaCarcelPenalesExportController::class)
    ->name('visita-carcel.exportar-penales')
    ->middleware(['auth', 'can:visita_carcel.gestionar']);

/*
|--------------------------------------------------------------------------
| CSRF REFRESH (para el monitor)
|--------------------------------------------------------------------------
*/
Route::get('/csrf-refresh', function () {
    return response()->json(['csrf' => csrf_token()]);
});

/*
|--------------------------------------------------------------------------
| EXPORTS (Excel)
|--------------------------------------------------------------------------
*/
Route::get('/audiencias/export/diaria', [AudienciasExportController::class, 'diaria'])
    ->name('audiencias.export.diaria');

/*
|--------------------------------------------------------------------------
| AUSENTISMO
|--------------------------------------------------------------------------
*/
Route::get('/ausentismo', [ausentismoController::class, 'index'])->name('ausentismo')->middleware(['auth', 'can:navbar.ausencias.visible']);
Route::get('/ausencias', [ausenciasController::class, 'index'])->name('ausencias');

/*
|--------------------------------------------------------------------------
| Correo Sistema de Audiencia.
|--------------------------------------------------------------------------
*/
Route::post('/programacion/mail/{fecha}', [PrograMailController::class, 'enviarProgramacionPorCorreo'])
    ->where('fecha', '\d{4}-\d{2}-\d{2}')
    ->middleware(['auth', 'can:navbar.audiencias.editable'])
    ->name('programacion.mail');

// TEST
Route::get('/preview/progra/{fecha?}', [PrograMailController::class, 'preview'])
    ->name('preview.progra');

Route::get('/ttypp', [TurnoController::class, 'ttyppWelcome'])->name('ttypp.welcome');

/*
|--------------------------------------------------------------------------
| DASHBOARD / PROFILE (Laravel Breeze / Jetstream)
|--------------------------------------------------------------------------
*/
Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

/*
|--------------------------------------------------------------------------
| SEGURIDAD (Roles / Permisos / Usuarios)
|--------------------------------------------------------------------------
*/
Route::prefix('seguridad')->name('seguridad.')->middleware(['auth'])->group(function () {
    Route::get('/roles', RolesPage::class)
        ->middleware('can:navbar.seguridad.visible')
        ->name('roles.index');

    Route::get('/roles/{role}/permisos', RolePermissionsPage::class)
        ->middleware('can:navbar.seguridad.visible')
        ->name('roles.permissions.edit');

    Route::get('/usuarios-roles', UsersRolesPage::class)
        ->middleware('can:navbar.seguridad.editable')
        ->name('users.index');

    Route::get('/auditoria', AuditPage::class)
        ->middleware('can:navbar.seguridad.visible')
        ->name('auditoria.index');
});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

Route::get('/cumpleanos', [CumpleanosController::class, 'index'])->name('cumpleanos.index');
