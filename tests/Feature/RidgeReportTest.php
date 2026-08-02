<?php

use App\Support\RidgeReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(fn () => Cache::forget('ridge:report'));

function openMeteoFixture(): array
{
    return [
        'current' => [
            'temperature_2m' => 21.4,
            'relative_humidity_2m' => 88,
            'weather_code' => 45,
            'wind_speed_10m' => 9.2,
            'visibility' => 800,
        ],
        'daily' => [
            'sunrise' => ['2026-08-02T05:41'],
            'sunset' => ['2026-08-02T18:29'],
        ],
    ];
}

test('ridge report maps open-meteo data including dense fog detection', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteoFixture())]);

    $report = RidgeReport::get();

    expect($report)->not->toBeNull()
        ->and($report['temperature'])->toBe(21.4)
        ->and($report['weatherLabel'])->toBe('Fog')
        ->and($report['fogLevel'])->toBe('dense')
        ->and($report['taalAlert'])->toBe(1)
        ->and($report['sunrise'])->toBe('2026-08-02T05:41');
});

test('ridge report returns null gracefully when the API is down', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response('oops', 500)]);

    expect(RidgeReport::get())->toBeNull();
});

test('ridge report caches the response', function () {
    Http::fake(['api.open-meteo.com/*' => Http::response(openMeteoFixture())]);

    RidgeReport::get();
    RidgeReport::get();

    Http::assertSentCount(1);
});
