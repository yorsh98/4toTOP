<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Clima del día en Santiago desde Open-Meteo (sin API key). Se guarda en caché
 * para que la portada no dependa de la API en cada visita; si falla, devuelve null.
 */
class WeatherService
{
    private const URL = 'https://api.open-meteo.com/v1/forecast';

    private const CACHE_KEY = 'front-office.weather';

    /**
     * Códigos WMO agrupados en el ícono que los representa.
     */
    private const KINDS = [
        'clear' => [0, 1],
        'partly' => [2],
        'cloudy' => [3],
        'fog' => [45, 48],
        'rain' => [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82],
        'snow' => [71, 73, 75, 77, 85, 86],
        'storm' => [95, 96, 99],
    ];

    private const LABELS = [
        0 => 'Despejado', 1 => 'Mayormente despejado', 2 => 'Parcialmente nublado', 3 => 'Nublado',
        45 => 'Niebla', 48 => 'Niebla con escarcha',
        51 => 'Llovizna débil', 53 => 'Llovizna', 55 => 'Llovizna intensa', 56 => 'Llovizna helada', 57 => 'Llovizna helada',
        61 => 'Lluvia débil', 63 => 'Lluvia', 65 => 'Lluvia intensa', 66 => 'Lluvia helada', 67 => 'Lluvia helada',
        71 => 'Nieve débil', 73 => 'Nieve', 75 => 'Nieve intensa', 77 => 'Granizo fino',
        80 => 'Chubascos', 81 => 'Chubascos', 82 => 'Chubascos fuertes', 85 => 'Chubascos de nieve', 86 => 'Chubascos de nieve',
        95 => 'Tormenta', 96 => 'Tormenta con granizo', 99 => 'Tormenta con granizo',
    ];

    /**
     * @return array{temperature: int, max: int, min: int, label: string, kind: string, is_day: bool}|null
     */
    public function today(): ?array
    {
        if (! config('services.weather.enabled')) {
            return null;
        }

        $cached = Cache::get(self::CACHE_KEY);
        if ($cached !== null) {
            return $cached ?: null;
        }

        $weather = $this->fetch();

        // Un fallo se recuerda poco tiempo para no frenar cada carga de la portada
        Cache::put(self::CACHE_KEY, $weather ?? false, $weather ? now()->addMinutes(30) : now()->addMinutes(5));

        return $weather;
    }

    private function fetch(): ?array
    {
        try {
            $data = Http::timeout(3)->get(self::URL, [
                'latitude' => config('services.weather.latitude'),
                'longitude' => config('services.weather.longitude'),
                'current' => 'temperature_2m,weather_code,is_day',
                'daily' => 'temperature_2m_max,temperature_2m_min',
                'timezone' => 'America/Santiago',
                'forecast_days' => 1,
            ])->throw()->json();

            $code = (int) data_get($data, 'current.weather_code');

            return [
                'temperature' => (int) round((float) data_get($data, 'current.temperature_2m')),
                'max' => (int) round((float) data_get($data, 'daily.temperature_2m_max.0')),
                'min' => (int) round((float) data_get($data, 'daily.temperature_2m_min.0')),
                'label' => self::LABELS[$code] ?? 'Sin dato',
                'kind' => collect(self::KINDS)->search(fn (array $codes) => in_array($code, $codes, true)) ?: 'cloudy',
                'is_day' => (bool) data_get($data, 'current.is_day', 1),
            ];
        } catch (Throwable $e) {
            Log::warning('No se pudo obtener el clima de la portada', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
