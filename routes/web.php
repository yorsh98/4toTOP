<?php

use App\Http\Controllers\SistOficioLibertadesController;
use App\Http\Controllers\tablasController;
use App\Http\Controllers\tabla2Controller;
use App\Http\Controllers\addfuncionarioController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\oficioController;
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
use App\Livewire\AudienciaForm;
use App\Livewire\MonitorAudiencias;

Route::view('/', 'welcome');
/*Route::get('/', function () {
    $response=http::get('http://127.0.0.1:8001/api/Oficio');
    $data=$response->json("oficio");
     // Obtener solo los valores de 'CausaAsig'
     //dd($causasAsignadas = array_column($data['oficio'], 'Numentregado'));
    foreach ($data as $Numentregado){
        echo $Numentregado['Numentregado'];
        echo "<br>";
    };
    
});*/



//Route::get('/', [SistOficioLibertadesController::class, 'index']);
//Route::get('/', [tablasController::class, 'index'])->name("tabla");





//Route::redirect('/', '/SistOficioLibertades')->name('SistOficioLibertades');


/*Route::get('/', function () {
    return redirect()->route('SistOficioLibertades.index');
});*/

Route::get('/oficios', [SistOficioLibertadesController::class, 'index'])->name('SistOficioLibertades.index');

Route::get('/SistOficioLibertades', [App\Http\Controllers\SistOficioLibertadesController::class, 'index' ])->name('SistOficioLibertades.index');
Route::get('/SistOficioLibertades', [App\Http\Controllers\SistOficioLibertadesController::class, 'create' ]);
Route::post('/SistOficioLibertades', [App\Http\Controllers\SistOficioLibertadesController::class, 'store' ])->name('SistOficioLibertades.store');



Route::get('/tablas', [App\Http\Controllers\tablasController::class, 'index' ]);
Route::get('/tabla2', [App\Http\Controllers\tabla2Controller::class, 'index' ])->name('tabla2');

Route::get('/addfuncionario', [App\Http\Controllers\addfuncionarioController::class, 'index'])->name('addfuncionario')->middleware(['auth']);
Route::post('/addfuncionario', [addfuncionarioController::class, 'store'])->name('addfuncionario.store');
Route::delete('/addfuncionario/{id}', [addfuncionarioController::class, 'destroy'])->name('addfuncionario.destroy');
Route::get('/SistOficioLibertades', [addfuncionarioController::class, 'enviarSolicitantes'])->name('SistOficioLibertades.index');


Route::get('/libertad', [App\Http\Controllers\libertadController::class, 'index'])->name('libertad');

Route::get('/oficio', [App\Http\Controllers\oficioController::class, 'index'])->name('oficio')->middleware(['auth']);
Route::delete('/Oficio/{id}', [App\Http\Controllers\oficioController::class, 'delete'])->name('Oficio.delete')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::patch('/Oficio/{id}', [OficioController::class, 'update'])->name('Oficio.update')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/Oficio-data', [App\Http\Controllers\oficioController::class, 'getData'])->name('Oficio.data');


Route::get('/libertad', [App\Http\Controllers\libertadController::class, 'index'])->name('libertad')->middleware(['auth']);
Route::delete('/Libertad/{id}', [App\Http\Controllers\libertadController::class, 'delete'])->name('Libertad.delete');
Route::patch('/Libertad/{id}', [libertadController::class, 'update'])->name('Libertad.update')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/Libertad-data', [App\Http\Controllers\libertadController::class, 'getData'])->name('libertad.data');

Route::get('/turno', [App\Http\Controllers\turnoController::class, 'index'])->name('turno')->middleware(['auth']);
Route::patch('/turno/{id}', [TurnoController::class, 'update'])->name('turno.update')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::get('/', [TurnoController::class, 'enviarTurno'])->name('Welcome.index');

Route::get('/guias', [App\Http\Controllers\guiasController::class, 'index'])->name('guias')->middleware(['auth']);
//Route::get('/guias/{id}/edit', [guiasController::class, 'edit'])->name('guias.edit');
//Route::patch('/guias/{id}', [guiasController::class, 'update'])->name('guias.update')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
//Route::delete('/guias/{id}', [guiasController::class, 'destroy'])->name('guias.destroy');
Route::post('/guias', [guiasController::class, 'store'])->name('guias.store');
//Route::get('/guias-datatable', [guiasController::class, 'datatable'])->name('guias.datatable')->middleware(['auth']);

Route::get('/guiastelefonicas', [guiastelefonicasController::class, 'index' ])->name('guiastelefonicas');
Route::get('/guias-data', [guiastelefonicasController::class, 'getGuias'])->name('guias.data');

Route::get('/acc', [accController::class, 'index' ])->name('acc');

Route::get('/doc', [docController::class, 'index' ])->name('doc');

Route::get('/form', [formController::class, 'index' ])->name('form');

Route::get('/proc', [procController::class, 'index' ])->name('proc');

Route::get('/progra', [prograController::class, 'index' ])->name('progra');
Route::get('/progra', [prograController::class, 'mostrar'])->name('progra');

Route::post('/enviar-solicitud', [SistOficioLibertadesController::class, 'enviarSolicitud'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('enviar-solicitud')
    ->middleware(['auth']);

//rutas livewire para modulos at Publico

Route::get('/AdmAudv', [AdmAudvController::class, 'index' ])->name('AdmAudv')->middleware(['auth']);

//adm
Route::get('/AdmAud', AudienciaForm::class)->name('AdmAud');

// Ruta para el monitor
Route::get('/monitor', MonitorAudiencias::class)->name('monitor');


Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
