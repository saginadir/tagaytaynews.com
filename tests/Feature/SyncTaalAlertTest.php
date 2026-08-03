<?php

use App\Models\Setting;
use App\Support\RawHttp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

const TAAL_INDEX_HTML = '<a href="https://www.phivolcs.dost.gov.ph/taal-volcano-summary-01-aug-2026/">Taal bulletin</a>';

function mockRawHttp(array $responses): void
{
    app()->instance(RawHttp::class, Mockery::mock(RawHttp::class, function (MockInterface $mock) use ($responses) {
        $mock->shouldReceive('get')->andReturnUsing(
            fn (string $url) => $responses[$url] ?? null,
        );
    }));
}

test('taal:sync-alert updates the level when the newest bulletin states it', function () {
    mockRawHttp([
        'https://www.phivolcs.dost.gov.ph/volcano-bulletin/' => TAAL_INDEX_HTML,
        'https://www.phivolcs.dost.gov.ph/taal-volcano-summary-01-aug-2026/' => '<html><body><p>Taal Volcano remains under Alert Level 2. The public is reminded...</p></body></html>',
    ]);

    $this->artisan('taal:sync-alert')
        ->expectsOutputToContain('updated: 1 → 2')
        ->assertSuccessful();

    expect(Setting::get('taal_alert_level'))->toBe('2');
});

test('taal:sync-alert never changes the level on unparseable bulletins', function () {
    Setting::set('taal_alert_level', '1');

    mockRawHttp([
        'https://www.phivolcs.dost.gov.ph/volcano-bulletin/' => TAAL_INDEX_HTML,
        'https://www.phivolcs.dost.gov.ph/taal-volcano-summary-01-aug-2026/' => '<html><body><img src="bulletin.jpg"></body></html>',
    ]);

    $this->artisan('taal:sync-alert')->assertSuccessful();

    expect(Setting::get('taal_alert_level'))->toBe('1');
});

test('taal:sync-alert tolerates fetch failures', function () {
    Setting::set('taal_alert_level', '1');
    mockRawHttp([]);

    $this->artisan('taal:sync-alert')->assertSuccessful();

    expect(Setting::get('taal_alert_level'))->toBe('1');
});
