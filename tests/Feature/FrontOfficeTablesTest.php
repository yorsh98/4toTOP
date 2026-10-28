<?php

use App\Livewire\AusentismoCalendario;
use App\Livewire\BitacoraPruebasPublicPage;
use App\Livewire\BitacoraPruebasViewer;
use App\Livewire\LibertadTabla;
use App\Livewire\MonitorAudiencias;
use App\Livewire\OficioTabla;
use App\Livewire\PublicFileConverterPage;
use App\Livewire\ServiciosMapaPublicPage;
use App\Livewire\TablaGuias;
use App\Models\Audiencia;
use App\Models\Ausentismo;
use App\Models\BitacoraPruebaDia;
use App\Models\BitacoraPruebaItem;
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

test('bitácora pública muestra pruebas y conserva la vista del backoffice', function () {
    $user = User::factory()->create(['name' => 'Ana']);
    $dia = BitacoraPruebaDia::create([
        'rit' => '100-2026', 'numero_dia' => 1, 'fecha_dia' => '2026-04-20', 'enlace_acta' => 'https://example.test/acta',
    ]);
    BitacoraPruebaItem::create([
        'bitacora_pruebas_dia_id' => $dia->id, 'nombre_prueba' => 'Declaración',
        'institucion_exhibe' => 'Fiscalía', 'ingresado_por_user_id' => $user->id,
    ]);

    $this->get('/bitacora-pruebas')->assertSuccessful()
        ->assertSee('Bitácora de pruebas')->assertSee('100-2026')->assertSee('Declaración')
        ->assertSee('Fiscalía')->assertSee('Ana')->assertSee('https://example.test/acta')
        ->assertSee('Ver pruebas')->assertSee('Abrir las pruebas del juicio 100-2026 en una pestaña nueva')
        ->assertSee('fo-table-wrap--glass', false);

    Livewire::test(BitacoraPruebasPublicPage::class)
        ->set('searchDetalle', 'Declaración')->assertSee('Declaración')
        ->set('searchDetalle', 'inexistente')->assertSee('No hay días que mostrar');
    Livewire::test(BitacoraPruebasViewer::class, ['showHero' => false])
        ->assertViewIs('livewire.bitacora-pruebas-viewer');
});

test('bitácora pública informa cuando no hay juicios', function () {
    $this->get('/bitacora-pruebas')->assertSuccessful()->assertSee('No hay juicios que mostrar');
});

test('mapa público usa su envoltorio y conserva el plano y su estado vacío', function () {
    $this->get('/servicios/mapa-publico')->assertSuccessful()
        ->assertSee('class="fo-mapa"', false)
        ->assertSee('Mapa de servicios')
        ->assertSee('Plano interactivo del piso 8')
        ->assertSee('No hay activos para los filtros actuales.');

    Livewire::test(ServiciosMapaPublicPage::class)
        ->assertViewIs('livewire.servicios-mapa-publica')
        ->call('selectPiso', 7)
        ->assertSee('Plano interactivo del piso 7');
});

test('programación diaria muestra audiencias y exporta la fecha elegida', function () {
    foreach (['Juicio Oral', 'Lectura de Sentencia', 'Audiencia Corta'] as $i => $tipo) {
        Audiencia::create([
            'fecha' => '2026-06-10', 'rit' => ($i + 101).'-2026', 'ruc' => '12345678-9',
            'sala' => (string) (801 + $i), 'hora_inicio' => '10:00', 'tipo_audiencia' => $tipo,
            'obs' => 'Observación de prueba', 'duracion' => '90 minutos', 'cta_zoom' => 'Zoom 123',
            'estado' => 'POR_REALIZARSE', 'anfitrion' => 'Ana', 'encargado_causa' => 'Causa', 'acta' => 'Acta', 'JuezR' => 'Juez',
            'acusados' => [['nombre_completo' => 'Persona Acusada', 'situacion' => 'Libre']],
        ]);
    }

    $this->get('/progra?fecha=2026-06-10')->assertSuccessful()
        ->assertSee('101-2026')->assertSee('102-2026')->assertSee('103-2026')
        ->assertSee('Sala 801')->assertSee('Sala 802')->assertSee('Sala 803')
        ->assertSee('Persona Acusada')->assertSee('Ana')
        ->assertSee('10 de junio 2026')->assertDontSee('10 De Junio 2026')
        ->assertSee('data-label="Hora"', false)->assertSee('data-label="Sala"', false)
        ->assertSee('data-label="RIT"', false)->assertSee('data-label="RUC"', false)
        ->assertSee('data-label="Tipo"', false)->assertSee('data-label="Anfitrión/a"', false)
        ->assertSee('Zoom 123')->assertSee('Observación de prueba')->assertSee('90 minutos')
        ->assertSee(route('audiencias.export.diaria'), false)
        ->assertSee('name="fecha" value="2026-06-10"', false)
        ->assertSee("document.querySelector('#progra-fecha').value", false)
        ->assertSee('aria-expanded="false"', false);
});

test('programación diaria informa los grupos vacíos', function () {
    $this->get('/progra?fecha=2026-06-10')->assertSuccessful()
        ->assertSee('Sin juicios orales programados para este día.')
        ->assertSee('Sin lecturas de sentencia programadas para este día.')
        ->assertSee('Sin audiencias cortas programadas para este día.')
        ->assertSee('No hay jueces ausentes registrados.');
});

test('monitor muestra el video y conserva el healthcheck', function () {
    $this->get('/Monitor')->assertSuccessful()
        ->assertSee('id="player"', false)
        ->assertSee('HEALTHCHECK_URL', false)
        ->assertSeeLivewire(MonitorAudiencias::class);
});

test('monitor muestra las audiencias de hoy del tipo actual', function () {
    Audiencia::create([
        'fecha' => today()->toDateString(), 'rit' => '123-2026', 'ruc' => '12345678-9',
        'sala' => '801', 'hora_inicio' => '10:00', 'tipo_audiencia' => 'Juicio Oral',
        'estado' => 'EN_CURSO', 'anfitrion' => 'Ana', 'encargado_causa' => 'Causa', 'acta' => 'Acta', 'JuezR' => 'Juez',
        'acusados' => [['nombre_completo' => 'Persona', 'situacion' => 'Libre']],
    ]);

    Livewire::test(MonitorAudiencias::class)->assertSee('123-2026')->assertSee('En curso');
});

test('el registro está disponible en el encabezado para invitados', function () {
    $this->get('/')->assertSuccessful()->assertSee('aria-label="Registrarse"', false);
    $this->actingAs(User::factory()->create())->get('/')
        ->assertSuccessful()->assertDontSee('aria-label="Registrarse"', false);
});

test('las pantallas de acceso enlazan entre sí', function () {
    $this->get('/login')->assertSuccessful()->assertSee('Crear una cuenta')->assertSee(route('register'), false);
    $this->get('/register')->assertSuccessful()->assertSee('¿Ya tienes cuenta?')->assertSee(route('login'), false);
});

test('la página de error muestra el fondo y el registro de navegación', function () {
    expect(view('errors.404')->render())
        ->toContain('centro-justicia-2560.webp')
        ->toContain('Registro de navegación');
});
