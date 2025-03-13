<?php

use App\Http\Controllers\SistOficioLibertadesController;
use App\Http\Controllers\tablasController;
use App\Http\Controllers\tabla2Controller;
use App\Http\Controllers\addfuncionarioController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\oficioController;
use App\Http\Controllers\libertadController;


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
Route::get('/tabla2', [App\Http\Controllers\tabla2Controller::class, 'index' ])->name('tabla2');;

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


Route::post('/enviar-solicitud', [SistOficioLibertadesController::class, 'enviarSolicitud'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('enviar-solicitud')
    ->middleware(['auth']);




Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
