<?php

use App\Livewire\AusentismoCalendario;
use App\Livewire\LibertadTabla;
use App\Livewire\OficioTabla;
use App\Livewire\PublicFileConverterPage;
use App\Livewire\TablaGuias;
use App\Models\Ausentismo;
use App\Models\Guias;
use App\Models\Libertad;
use App\Models\Oficio;
use App\Models\TtyppRegistro;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('ttypp muestra los datos y el detalle de la fecha seleccionada', function () {
    TtyppRegistro::factory()->create([
        'fecha_asignacion' => '2026-06-10',
        'rit' => '168-2026',
        'ruc' => '2600012345-6',
        'tipo_audiencia' => 'Juicio oral',
        'encargada_ti_mp' => 'María',
        'acta' => 'Acta Tres',
        'jueces' => 'Juez P',
        'sala' => null,
    ]);

    $this->get(route('ttypp.welcome', ['fecha' => '2026-06-10']))
        ->assertSuccessful()
        ->assertSee('168-2026')
        ->assertSee('2600012345-6')
        ->assertSee('Juicio oral')
        ->assertSee('María')
        ->assertSee('Acta Tres')
        ->assertSee('Juez P')
        ->assertSee('class="fo-dash"', false)
        ->assertSee('aria-expanded="false"', false);
});

test('ttypp informa cuando no hay registros', function () {
    $this->get(route('ttypp.welcome', ['fecha' => '2026-06-10']))
        ->assertSuccessful()
        ->assertSee('No hay registros TTyPP para miércoles 10 de junio de 2026. Prueba con otro día.')
        ->assertSee('Hoy');
});

test('ttypp permite navegar por fechas', function () {
    Carbon::setTestNow(Carbon::parse('2026-06-09 10:00:00', 'America/Santiago'));

    try {
        $this->get(route('ttypp.welcome', ['fecha' => '2026-06-10']))
            ->assertSuccessful()
            ->assertSee('name="fecha" value="2026-06-10"', false)
            ->assertSee('?fecha=2026-06-09')
            ->assertSee('?fecha=2026-06-11');
    } finally {
        Carbon::setTestNow();
    }
});

test('guías públicas muestran contactos y enlaces', function () {
    Guias::create([
        'nombre_completo' => 'María Pérez',
        'institucion' => 2,
        'telefono1' => '+56 2 1234 5678',
        'email' => 'maria@example.test',
    ]);

    $this->get(route('guiastelefonicas'))
        ->assertSuccessful()
        ->assertSee('María Pérez')
        ->assertSee('Gendarmeria')
        ->assertSee('href="tel:+56212345678"', false)
        ->assertSee('href="mailto:maria@example.test"', false);
});

test('guías públicas permiten buscar y muestran estados vacíos', function () {
    Guias::create(['nombre_completo' => 'María Pérez', 'institucion' => 2]);

    Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->set('search', 'María')
        ->assertSee('María Pérez')
        ->set('search', 'No existe')
        ->assertDontSee('María Pérez')
        ->assertSee('Ningún contacto coincide');

    Guias::query()->delete();
    Livewire::test(TablaGuias::class, ['modo' => 'lectura'])
        ->assertSee('Aún no hay guías cargadas.');
});

test('guías en modo full conservan la vista del backoffice', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo('guias.ver');
    $this->actingAs($user);

    Livewire::test(TablaGuias::class, ['modo' => 'full'])
        ->assertViewIs('livewire.tabla-guias');
});

test('ausencias muestra el mes actual y permite abrir y cerrar un día', function () {
    $hoy = now();
    Ausentismo::create([
        'funcionario_nombre' => 'María Pérez',
        'cargo' => 'Administrativa',
        'tipo_permiso' => 'Licencia médica',
        'fecha_inicio' => $hoy->toDateString(),
        'fecha_termino' => $hoy->toDateString(),
    ]);

    $this->get('/ausencias')
        ->assertSuccessful()
        ->assertSee(mb_convert_case($hoy->translatedFormat('F Y'), MB_CASE_TITLE, 'UTF-8'))
        ->assertSee('María Pérez');

    Livewire::test(AusentismoCalendario::class)
        ->call('openDay', $hoy->toDateString())
        ->assertSee('Ausencias del')
        ->assertSee('Administrativa')
        ->call('closeModal')
        ->assertDontSee('Ausencias del');
});

test('ausencias informa cuando no hay personas ausentes en el día', function () {
    Livewire::test(AusentismoCalendario::class)
        ->call('openDay', now()->toDateString())
        ->assertSee('Nadie ausente este día.');
});

test('convertir archivos muestra la cabecera y el historial de la sesión', function () {
    $this->get(route('conversions.public'))->assertSuccessful();
    $this->get(route('conversions.public'))
        ->assertSuccessful()
        ->assertSee('class="fo-page-title"', false)
        ->assertSee('Convertir archivos')
        ->assertSee('class="fo-table-wrap fo-table-wrap--glass fo-conv-table', false);

    Livewire::test(PublicFileConverterPage::class)
        ->set('actorName', 'Ana')
        ->set('file', UploadedFile::fake()->create('acta.pdf', 10, 'application/pdf'))
        ->call('convert')
        ->assertHasNoErrors()
        ->assertSee('acta.pdf')
        ->assertSee('data-label="Estado"', false);
});

test('últimos oficios y libertades muestran registros públicos y permiten buscar', function (string $model, string $component, string $url) {
    $model::create(['Numentregado' => 123, 'año' => now()->year, 'CausaAsig' => 'C-123', 'UserSolicitante' => 'Ana', 'UserDirigido' => 'Prueba']);
    $eliminado = $model::create(['Numentregado' => 124, 'año' => now()->year, 'CausaAsig' => 'Eliminada', 'UserSolicitante' => 'Ana', 'UserDirigido' => 'Prueba']);
    $eliminado->delete();

    $this->get($url)->assertSuccessful()
        ->assertSee('123-'.now()->year)
        ->assertSee('C-123')
        ->assertDontSee('Eliminada')
        ->assertDontSee('wire:click="toggleEliminados"', false)
        ->assertSee('aria-sort=', false);

    Livewire::test($component, ['modo' => 'vista'])
        ->set('search', 'No existe')
        ->assertSee('No se encontraron registros')
        ->set('search', 'C-123')
        ->assertSee('C-123');
})->with([
    [Oficio::class, OficioTabla::class, '/tablas'],
    [Libertad::class, LibertadTabla::class, '/tabla2'],
]);

test('oficios y libertades en modo full conservan sus vistas', function (string $component, string $permission, string $view) {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    Livewire::test($component, ['modo' => 'full'])->assertViewIs($view);
})->with([
    [OficioTabla::class, 'oficios.ver', 'livewire.oficio-tabla'],
    [LibertadTabla::class, 'libertades.ver', 'livewire.libertad-tabla'],
]);

test('las pantallas de autenticación usan el marco público y campos accesibles', function (string $url, string $title) {
    $this->get($url)->assertSuccessful()
        ->assertSee('data-app-view', false)
        ->assertSee('fo-auth-panel', false)
        ->assertSee($title)
        ->assertSee('aria-invalid="false"', false);
})->with([
    ['/login', 'Ingresar'],
    ['/register', 'Registro'],
    ['/forgot-password', 'Recuperar contraseña'],
    ['/reset-password/token', 'Restablecer contraseña'],
]);
