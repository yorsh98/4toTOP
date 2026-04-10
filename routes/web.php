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
use App\Http\Controllers\docController;
use App\Http\Controllers\formController;
use App\Http\Controllers\guiasController;
use App\Http\Controllers\guiastelefonicasController;
use App\Http\Controllers\libertadController;
use App\Http\Controllers\migraunijudController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\oficiolibertadController;
use App\Http\Controllers\procController;
use App\Http\Controllers\prograController;
use App\Http\Controllers\PrograMailController;
use App\Http\Controllers\SeguridadController;
use App\Http\Controllers\SistOficioLibertadesController;
use App\Http\Controllers\tabla2Controller;
use App\Http\Controllers\tablasController;
use App\Http\Controllers\TurnoController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
    Route::get('/', [addfuncionarioController::class, 'index'])->name('index')->middleware('can:navbar.funcionarios.visible');
    Route::post('/', [addfuncionarioController::class, 'store'])->name('store')->middleware('can:navbar.funcionarios.editable');
    Route::delete('/{id}', [addfuncionarioController::class, 'destroy'])->name('destroy')->middleware('can:navbar.funcionarios.editable');
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
Route::get('/turno', [TurnoController::class, 'index'])->name('turno')->middleware(['auth', 'can:navbar.turnos.visible']);
Route::patch('/turno/{id}', [TurnoController::class, 'update'])
    ->name('turno.update')
    ->middleware(['auth', 'can:navbar.turnos.editable'])
    ->withoutMiddleware([PreventRequestForgery::class]);

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
Route::get('/form', [formController::class, 'index'])->name('form');
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
    Route::get('/mostrar', [prograController::class, 'mostrar'])->name('mostrar');
});

/*
|--------------------------------------------------------------------------
| ENVÍO DE SOLICITUD (manteniendo tu configuración)
|--------------------------------------------------------------------------
*/
Route::post('/enviar-solicitud', [SistOficioLibertadesController::class, 'enviarSolicitud'])
    ->withoutMiddleware([PreventRequestForgery::class])
    ->middleware(['auth', 'can:navbar.oficios_libertades.editable'])
    ->name('enviar-solicitud');

/*
|--------------------------------------------------------------------------
| LIVEWIRE PÚBLICO (según tu comentario original)
|--------------------------------------------------------------------------
*/
Route::get('/AdmAudv', [AdmAudvController::class, 'index'])->name('AdmAudv')->middleware(['auth', 'can:navbar.audiencias.visible']);
Route::get('/Monitor', [MonitorController::class, 'index'])->name('Monitor')->middleware(['auth', 'can:navbar.audiencias.visible']);

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
    ->middleware(['auth', 'can:navbar.audiencias.editable'])
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
Route::get('/programacion/mail/{fecha}', [PrograMailController::class, 'enviarProgramacionPorCorreo'])
    ->where('fecha', '\d{4}-\d{2}-\d{2}')
    ->middleware(['auth', 'can:navbar.audiencias.editable'])
    ->name('programacion.mail');

// TEST
Route::get('/preview/progra/{fecha?}', [PrograMailController::class, 'preview'])
    ->name('preview.progra');

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
    Route::get('/roles', [SeguridadController::class, 'rolesIndex'])
        ->middleware('can:navbar.seguridad.visible')
        ->name('roles.index');

    Route::post('/roles', [SeguridadController::class, 'rolesStore'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('roles.store');

    Route::delete('/roles/{role}', [SeguridadController::class, 'rolesDestroy'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('roles.destroy');

    Route::get('/roles/{role}/permisos', [SeguridadController::class, 'rolePermissionsEdit'])
        ->middleware('can:navbar.seguridad.visible')
        ->name('roles.permissions.edit');

    Route::put('/roles/{role}/permisos', [SeguridadController::class, 'rolePermissionsUpdate'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('roles.permissions.update');

    Route::get('/usuarios-roles', [SeguridadController::class, 'usersRolesIndex'])
        ->middleware('can:navbar.seguridad.visible')
        ->name('users.index');

    Route::put('/usuarios/{user}/roles', [SeguridadController::class, 'usersRolesUpdate'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('users.roles.update');

    Route::post('/usuarios/{user}/send-reset-password', [SeguridadController::class, 'usersSendResetPasswordLink'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('users.send-reset-password');

    Route::patch('/usuarios/{user}/suspension', [SeguridadController::class, 'usersSetSuspension'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('users.suspension');

    Route::patch('/usuarios/{user}/approve', [SeguridadController::class, 'usersApprove'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('users.approve');

    Route::patch('/usuarios/{user}/reject', [SeguridadController::class, 'usersReject'])
        ->middleware('can:navbar.seguridad.editable')
        ->name('users.reject');

    Route::get('/auditoria', [SeguridadController::class, 'auditIndex'])
        ->middleware('can:navbar.seguridad.visible')
        ->name('auditoria.index');
});

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
