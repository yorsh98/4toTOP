<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

// Controllers
use App\Http\Controllers\SistOficioLibertadesController;
use App\Http\Controllers\tablasController;
use App\Http\Controllers\tabla2Controller;
use App\Http\Controllers\addfuncionarioController;
use App\Http\Controllers\oficiolibertadController;
use App\Http\Controllers\libertadController;
use App\Http\Controllers\TurnoController;
use App\Http\Controllers\welcomeController;
use App\Http\Controllers\guiasController;
use App\Http\Controllers\guiastelefonicasController;
use App\Http\Controllers\accController;
use App\Http\Controllers\docController;
use App\Http\Controllers\formController;
use App\Http\Controllers\procController;
use App\Http\Controllers\prograController;
use App\Http\Controllers\AdmAudvController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\AudienciasExportController;
use App\Http\Controllers\ausentismoController;
use App\Http\Controllers\ausenciasController;

/*
|--------------------------------------------------------------------------
| HOME
|--------------------------------------------------------------------------
| Elige una sola raíz para evitar colisiones.
| Dejamos la vista "welcome" en "/" y movemos enviarTurno a otra ruta.
*/
Route::view('/', 'welcome')->name('welcome');
// Si quieres que enviarTurno sea tu “home”, comenta la línea anterior y descomenta la siguiente:
// Route::get('/', [TurnoController::class, 'enviarTurno'])->name('welcome');

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
    Route::get('/', [addfuncionarioController::class, 'index'])->name('index');
    Route::post('/', [addfuncionarioController::class, 'store'])->name('store');
    Route::delete('/{id}', [addfuncionarioController::class, 'destroy'])->name('destroy');
});

/*
|--------------------------------------------------------------------------
| OFICIO / LIBERTAD (modulos antiguos)
|--------------------------------------------------------------------------
*/
Route::get('/oficiolibertad', [oficiolibertadController::class, 'index'])->name('oficiolibertad')->middleware(['auth']);
// (Si usas libertadController, define aquí sus rutas con nombres únicos)

/*
|--------------------------------------------------------------------------
| TURNOS
|--------------------------------------------------------------------------
*/
Route::get('/turno', [TurnoController::class, 'index'])->name('turno')->middleware(['auth']);
Route::patch('/turno/{id}', [TurnoController::class, 'update'])
    ->name('turno.update')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

/*
|--------------------------------------------------------------------------
| PÁGINAS VARIAS
|--------------------------------------------------------------------------
*/
Route::get('/tablas', [tablasController::class, 'index']);
Route::get('/tabla2', [tabla2Controller::class, 'index'])->name('tabla2');

Route::get('/guias', [guiasController::class, 'index'])->name('guias')->middleware(['auth']);
Route::get('/guiastelefonicas', [guiastelefonicasController::class, 'index'])->name('guiastelefonicas');

Route::get('/acc', [accController::class, 'index'])->name('acc');
Route::get('/doc', [docController::class, 'index'])->name('doc');
Route::get('/form', [formController::class, 'index'])->name('form');
Route::get('/proc', [procController::class, 'index'])->name('proc');

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
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->middleware(['auth'])
    ->name('enviar-solicitud');

/*
|--------------------------------------------------------------------------
| LIVEWIRE PÚBLICO (según tu comentario original)
|--------------------------------------------------------------------------
*/
Route::get('/AdmAudv', [AdmAudvController::class, 'index'])->name('AdmAudv')->middleware(['auth']);
Route::get('/Monitor', [MonitorController::class, 'index'])->name('Monitor');

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
Route::get('/ausentismo', [ausentismoController::class, 'index'])->name('ausentismo')->middleware(['auth']);
Route::get('/ausencias', [ausenciasController::class, 'index'])->name('ausencias');

/*
|--------------------------------------------------------------------------
| DASHBOARD / PROFILE (Laravel Breeze / Jetstream)
|--------------------------------------------------------------------------
*/
Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');
Route::view('profile', 'profile')->middleware(['auth'])->name('profile');

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
