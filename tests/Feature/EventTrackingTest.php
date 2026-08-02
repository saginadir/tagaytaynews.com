<?php

use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('event beacon stores valid events with a daily session hash', function () {
    $this->postJson('/t', [
        'type' => 'feature',
        'path' => '/quiz',
        'target' => 'quiz:complete',
        'value' => 8,
    ])->assertNoContent();

    $event = Event::firstOrFail();
    expect($event->type)->toBe('feature')
        ->and($event->path)->toBe('/quiz')
        ->and($event->target)->toBe('quiz:complete')
        ->and($event->value)->toBe(8)
        ->and($event->session)->toMatch('/^[a-f0-9]{64}$/');
});

test('event beacon rejects unknown types and skips bots and admin paths', function () {
    $this->postJson('/t', ['type' => 'hack', 'path' => '/'])->assertUnprocessable();

    $adminPath = config('admin.path') ?: 'x-ops';
    $this->postJson('/t', ['type' => 'click', 'path' => "/{$adminPath}/articles"]);
    $this->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; bingbot/2.0)'])
        ->postJson('/t', ['type' => 'click', 'path' => '/']);

    expect(Event::count())->toBe(0);
});

test('analytics:report shows engagement metrics', function () {
    Event::create(['session' => str_repeat('a', 64), 'type' => 'time', 'path' => '/quiz', 'target' => '85', 'value' => 42000]);
    Event::create(['session' => str_repeat('a', 64), 'type' => 'feature', 'path' => '/quiz', 'target' => 'quiz:complete', 'value' => 9]);
    Event::create(['session' => str_repeat('b', 64), 'type' => 'outbound', 'path' => '/', 'target' => 'https://www.phivolcs.dost.gov.ph/x']);

    $this->artisan('analytics:report')
        ->expectsOutputToContain('Avg engaged time: 42s')
        ->expectsOutputToContain('quiz:complete')
        ->expectsOutputToContain('www.phivolcs.dost.gov.ph')
        ->assertSuccessful();
});
