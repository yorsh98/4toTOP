<?php

use App\Http\Controllers\SistOficioLibertadesController;
use App\Http\Controllers\tablasController;
use App\Http\Controllers\tabla2Controller;
use App\Http\Controllers\addfuncionarioController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use App\Http\Controllers\oficioController;


//Route::view('/', 'welcome');
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




Route::get('/oficios', [SistOficioLibertadesController::class, 'index'])->name('SistOficioLibertades.index');

Route::get('/SistOficioLibertades', [App\Http\Controllers\SistOficioLibertadesController::class, 'index' ])->name('SistOficioLibertades.index');
Route::get('/SistOficioLibertades', [App\Http\Controllers\SistOficioLibertadesController::class, 'create' ]);
Route::post('/SistOficioLibertades', [App\Http\Controllers\SistOficioLibertadesController::class, 'store' ])->name('SistOficioLibertades.store');



Route::get('/tablas', [App\Http\Controllers\tablasController::class, 'index' ]);
Route::get('/tabla2', [App\Http\Controllers\tabla2Controller::class, 'index' ])->name('tabla2');;

Route::get('/addfuncionario', [App\Http\Controllers\addfuncionarioController::class, 'index'])->name('addfuncionario');
Route::post('/addfuncionario', [addfuncionarioController::class, 'store'])->name('addfuncionario.store');
Route::delete('/addfuncionario/{id}', [addfuncionarioController::class, 'destroy'])->name('addfuncionario.destroy');
Route::get('/SistOficioLibertades', [addfuncionarioController::class, 'enviarSolicitantes'])->name('SistOficioLibertades.index');


Route::get('/libertad', [App\Http\Controllers\libertadController::class, 'index'])->name('libertad');

Route::get('/oficio', [App\Http\Controllers\oficioController::class, 'index'])->name('oficio');
Route::delete('/Oficio/{id}', [App\Http\Controllers\oficioController::class, 'delete'])->name('Oficio.delete');
Route::patch('/Oficio/{id}', [OficioController::class, 'update'])->name('Oficio.update');



Route::post('/enviar-solicitud', [SistOficioLibertadesController::class, 'enviarSolicitud'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('enviar-solicitud');




Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
