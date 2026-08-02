<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Live conditions on the Tagaytay ridge: current weather from Open-Meteo
 * (free, no key) plus the manually maintained Taal alert level.
 */
class RidgeReport
{
    private const CACHE_KEY = 'ridge:report';

    private const TTL = 900; // 15 minutes

    /** WMO weather interpretation codes → label. */
    private const WEATHER_LABELS = [
        0 => 'Clear sky', 1 => 'Mainly clear', 2 => 'Partly cloudy', 3 => 'Overcast',
        45 => 'Fog', 48 => 'Icy fog',
        51 => 'Light drizzle', 53 => 'Drizzle', 55 => 'Heavy drizzle',
        56 => 'Freezing drizzle', 57 => 'Freezing drizzle',
        61 => 'Light rain', 63 => 'Rain', 65 => 'Heavy rain',
        66 => 'Freezing rain', 67 => 'Freezing rain',
        71 => 'Snow', 73 => 'Snow', 75 => 'Heavy snow', 77 => 'Snow grains',
        80 => 'Light showers', 81 => 'Showers', 82 => 'Heavy showers',
        85 => 'Snow showers', 86 => 'Snow showers',
        95 => 'Thunderstorm', 96 => 'Thunderstorm with hail', 99 => 'Thunderstorm with hail',
    ];

    /**
     * @return array{temperature: float, humidity: int, windKph: float, visibilityM: int,
     *     weatherLabel: string, fogLevel: 'none'|'patches'|'dense', sunrise: string,
     *     sunset: string, taalAlert: int, updatedAt: string}|null
     */
    public static function get(): ?array
    {
        return Cache::remember(self::CACHE_KEY, self::TTL, function (): ?array {
            try {
                $response = Http::timeout(10)->get('https://api.open-meteo.com/v1/forecast', [
                    'latitude' => 14.1153,
                    'longitude' => 120.9621,
                    'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m,visibility',
                    'daily' => 'sunrise,sunset',
                    'timezone' => 'Asia/Manila',
                    'forecast_days' => 1,
                ]);
            } catch (\Throwable $e) {
                Log::error('Ridge report fetch failed', ['error' => $e->getMessage()]);

                return null;
            }

            if (! $response->ok()) {
                Log::error('Ridge report non-200', ['status' => $response->status()]);

                return null;
            }

            $current = $response->json('current') ?? [];
            $daily = $response->json('daily') ?? [];
            $visibility = (int) ($current['visibility'] ?? 99999);

            return [
                'temperature' => (float) ($current['temperature_2m'] ?? 0),
                'humidity' => (int) ($current['relative_humidity_2m'] ?? 0),
                'windKph' => (float) ($current['wind_speed_10m'] ?? 0),
                'visibilityM' => $visibility,
                'weatherLabel' => self::WEATHER_LABELS[(int) ($current['weather_code'] ?? -1)] ?? 'Unknown',
                'fogLevel' => $visibility < 1000 ? 'dense' : ($visibility < 5000 ? 'patches' : 'none'),
                'sunrise' => $daily['sunrise'][0] ?? '',
                'sunset' => $daily['sunset'][0] ?? '',
                'taalAlert' => (int) Setting::get('taal_alert_level', '1'),
                'updatedAt' => now()->toIso8601String(),
            ];
        });
    }
}
