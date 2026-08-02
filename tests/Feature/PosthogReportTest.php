<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('posthog:report runs hogql queries and prints results', function () {
    config([
        'services.posthog.personal_key' => 'phx_test',
        'services.posthog.project_id' => '12345',
        'services.posthog.api_host' => 'https://eu.posthog.com',
    ]);

    Http::fake(['eu.posthog.com/api/projects/12345/query/' => Http::response([
        'results' => [['https://tagaytaynews.com/', 42]],
    ])]);

    $this->artisan('posthog:report')
        ->expectsOutputToContain('Top pages (views)')
        ->expectsOutputToContain('Rage clicks by page')
        ->expectsOutputToContain('Session duration')
        ->assertSuccessful();

    Http::assertSentCount(3);
});

test('posthog:report fails helpfully without credentials', function () {
    config(['services.posthog.personal_key' => null, 'services.posthog.project_id' => null]);

    $this->artisan('posthog:report')->assertFailed();
});
