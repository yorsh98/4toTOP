<?php

use App\Services\WeatherService;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => config(['services.weather.enabled' => true]));

function fakeOpenMeteo(int $code = 61, int $isDay = 1): void
{
    Http::fake(['api.open-meteo.com/*' => Http::response([
        'current' => ['temperature_2m' => 17.6, 'weather_code' => $code, 'is_day' => $isDay],
        'daily' => ['temperature_2m_max' => [23.4], 'temperature_2m_min' => [8.5]],
    ])]);
}

test('welcome shows today weather next to the clock', function () {
    fakeOpenMeteo(61);

    $this->get(route('welcome'))
        ->assertSuccessful()
        ->assertSee('Hora y clima en Santiago')
        ->assertSee('18°')
        ->assertSee('Lluvia débil')
        ->assertSee('Máx 23° · Mín 9°')
        ->assertSee('fo-wx-icon--rain', false);
});

test('weather is cached between visits', function () {
    fakeOpenMeteo(0, 0);
    $service = app(WeatherService::class);

    expect($service->today())->toMatchArray(['kind' => 'clear', 'is_day' => false, 'label' => 'Despejado']);
    $service->today();

    Http::assertSentCount(1);
});

test('welcome still loads when the weather api fails', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response('error', 500)]);

    $this->get(route('welcome'))
        ->assertSuccessful()
        ->assertSee('Hora en Santiago')
        ->assertDontSee('fo-wx-icon', false);
});

test('weather is skipped when disabled', function () {
    config(['services.weather.enabled' => false]);
    Http::fake();

    expect(app(WeatherService::class)->today())->toBeNull();
    Http::assertNothingSent();
});
