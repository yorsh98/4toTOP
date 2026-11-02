<?php

use App\Models\Audiencia;
use App\Models\User;
use App\Services\FrontOfficeCatalog;

test('welcome lists every public access from the catalog', function () {
    $response = $this->get(route('welcome'))->assertSuccessful();

    foreach (app(FrontOfficeCatalog::class)->items() as $item) {
        $response->assertSee($item['title']);

        if ($item['href'] !== null) {
            $response->assertSee($item['href'], false);
        }
    }
});

test('welcome summarizes today hearings by state', function () {
    $base = [
        'fecha' => today(),
        'ruc' => '11111111-1',
        'sala' => 'Sala 1',
        'hora_inicio' => '09:00',
        'tipo_audiencia' => 'Juicio Oral',
        'encargado_causa' => 'Encargada',
        'acta' => 'Acta',
        'JuezR' => 'Juez',
        'obs' => '',
        'acusados' => [],
    ];

    Audiencia::create([...$base, 'rit' => '10-2026', 'estado' => 'EN_CURSO']);
    Audiencia::create([...$base, 'rit' => '11-2026', 'estado' => 'FINALIZADA']);
    Audiencia::create([...$base, 'rit' => '12-2026', 'estado' => 'FINALIZADA', 'fecha' => today()->subDay()]);

    $this->get(route('welcome'))
        ->assertSuccessful()
        ->assertSee('2</b> programadas', false)
        ->assertSee('1 en curso · 1 finalizadas');
});

test('oficios page shows the assigned number after a request', function () {
    $this->withSession(['success' => 'ok', 'tipo' => 'libertad', 'NumEntregado' => 42, 'año' => 2026])
        ->get(route('SistOficioLibertades.index'))
        ->assertSuccessful()
        ->assertSee('Número de libertad asignado')
        ->assertSee('42-2026');
});

test('internal module cards open as apps and external ones do not', function () {
    $html = $this->get(route('welcome'))->assertSuccessful()->getContent();

    foreach (app(FrontOfficeCatalog::class)->items() as $item) {
        if ($item['href'] === null) {
            continue;
        }

        preg_match('/<a\s[^>]*href="'.preg_quote(e($item['href']), '/').'"[^>]*class="[^"]*fo-access[^>]*>/', $html, $card);

        expect($card)->not->toBeEmpty();
        empty($item['external'])
            ? expect($card[0])->toContain('data-app-open')
            : expect($card[0])->not->toContain('data-app-open');
    }
});

test('anonymous public pages allow back button caching', function () {
    $response = $this->get(route('bitacora-pruebas.public'))->assertSuccessful();

    expect($response->headers->get('Cache-Control'))->toContain('no-cache', 'private')->not->toContain('no-store');
});

test('authenticated public pages retain no-store', function () {
    $response = $this->actingAs(User::factory()->create())->get(route('bitacora-pruebas.public'))->assertSuccessful();

    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('server does not fix the theme on html so the head script decides', function () {
    foreach ([$this->get('/'), $this->get(route('login')), $this->get(route('Monitor')), $this->get('/no-existe-esta-pagina')] as $response) {
        expect($response->getContent())
            ->not->toMatch('/<html[^>]*data-theme/')
            ->toContain("matchMedia('(prefers-color-scheme: dark)')");
    }
});
