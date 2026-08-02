<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Poll;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

const PHIVOLCS_INDEX = <<<'HTML'
<html><body>
<a href="https://www.phivolcs.dost.gov.ph/taal-volcano-summary-of-24hr-observation-09-july-2025-12-00-am/">Taal Volcano Summary of 24Hr Observation 09 July 2025</a>
<a href="https://www.phivolcs.dost.gov.ph/bulkang-taal-buod-ng-24-oras-na-pagmamanman-09-hulyo-2025-alas-12-ng-umaga/">Bulkang Taal Buod ng 24 Oras na Pagmamanman 09 Hulyo 2025</a>
<a href="https://www.phivolcs.dost.gov.ph/mayon-volcano-summary-of-24hr-observation-09-july-2025/">Mayon Volcano Summary of 24Hr Observation 09 July 2025</a>
</body></html>
HTML;

beforeEach(function () {
    Category::create(['name' => 'Taal Volcano', 'slug' => 'taal-volcano']);
    Source::create(['name' => 'PHIVOLCS', 'url' => 'https://www.phivolcs.dost.gov.ph', 'tier' => 1]);
});

test('news:watch-phivolcs queues drafts only for new Taal bulletins', function () {
    $this->mock(\App\Support\RawHttp::class)
        ->shouldReceive('get')
        ->once()
        ->with('https://www.phivolcs.dost.gov.ph/volcano-bulletin/')
        ->andReturn(PHIVOLCS_INDEX);

    $this->artisan('news:watch-phivolcs')->assertSuccessful();

    $drafts = Article::where('status', 'draft')->get();
    expect($drafts)->toHaveCount(2) // Taal only, not Mayon
        ->and($drafts->pluck('source_url')->map(fn ($u) => str_ends_with($u, '/'))->unique()->all())->toBe([true]);
});

test('news:watch-phivolcs dedupes already-queued bulletins', function () {
    Article::create([
        'title' => 'Existing write-up',
        'body' => 'body',
        'category_id' => Category::firstOrFail()->id,
        'source_url' => 'https://www.phivolcs.dost.gov.ph/taal-volcano-summary-of-24hr-observation-09-july-2025-12-00-am/',
        'status' => 'published',
    ]);

    $this->mock(\App\Support\RawHttp::class)
        ->shouldReceive('get')->once()->andReturn(PHIVOLCS_INDEX);

    $this->artisan('news:watch-phivolcs')->assertSuccessful();

    expect(Article::where('status', 'draft')->count())->toBe(1); // only the Filipino one
});

test('news:watch-phivolcs tolerates fetch failure', function () {
    $this->mock(\App\Support\RawHttp::class)
        ->shouldReceive('get')->once()->andReturn(null);

    $this->artisan('news:watch-phivolcs')->assertSuccessful();
    expect(Article::count())->toBe(0);
});

test('analytics:report prints traffic and content digest', function () {
    Poll::create(['question' => 'Best bulalo?', 'slug' => 'b', 'is_active' => true]);
    \App\Models\PageView::create(['path' => '/', 'referrer' => 'https://google.com/x', 'ip_hash' => str_repeat('a', 64)]);

    $this->artisan('analytics:report')
        ->expectsOutputToContain('Page views: 1')
        ->expectsOutputToContain('google.com')
        ->expectsOutputToContain('Best bulalo?')
        ->assertSuccessful();
});
