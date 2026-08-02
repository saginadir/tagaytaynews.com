<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('clarity:report prints metrics from the export api', function () {
    config(['services.clarity.token' => 'test-token']);

    Http::fake(['clarity.ms/export-data/*' => Http::response([
        [
            'metricName' => 'Traffic',
            'information' => [
                ['totalSessionCount' => '42', 'URL' => 'https://tagaytaynews.com/quiz'],
            ],
        ],
    ])]);

    $this->artisan('clarity:report')
        ->expectsOutputToContain('Traffic (1 rows)')
        ->expectsOutputToContain('totalSessionCount')
        ->assertSuccessful();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-token'));
});

test('clarity:report fails helpfully without a token', function () {
    config(['services.clarity.token' => null]);

    $this->artisan('clarity:report')->assertFailed();
});

test('clarity:report handles quota exhaustion', function () {
    config(['services.clarity.token' => 'test-token']);
    Http::fake(['clarity.ms/export-data/*' => Http::response('', 429)]);

    $this->artisan('clarity:report')->assertFailed();
});
