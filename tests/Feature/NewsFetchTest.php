<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

function rssFixture(): string
{
    return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Test Feed</title>
    <item>
      <title>Fog blankets Tagaytay ridge, motorists advised to slow down</title>
      <link>https://example.com/fog-tagaytay</link>
      <description><![CDATA[<p>Thick fog covered Tagaytay City early Monday, reducing visibility along the ridge.</p>]]></description>
      <pubDate>Mon, 03 Aug 2026 08:00:00 +0800</pubDate>
    </item>
    <item>
      <title>PHIVOLCS records increased activity at Taal Volcano</title>
      <link>https://example.com/taal-activity</link>
      <description>Volcanic smog observed over the Taal caldera.</description>
      <pubDate>Mon, 03 Aug 2026 09:00:00 +0800</pubDate>
    </item>
    <item>
      <title>Stock market closes higher in Manila trading</title>
      <link>https://example.com/stocks-manila</link>
      <description>Shares rallied on Monday.</description>
      <pubDate>Mon, 03 Aug 2026 10:00:00 +0800</pubDate>
    </item>
  </channel>
</rss>
XML;
}

function activeSource(array $overrides = []): Source
{
    return Source::create(array_merge([
        'name' => 'Test Outlet',
        'url' => 'https://example.com',
        'feed_url' => 'https://example.com/feed',
        'tier' => 2,
        'is_active' => true,
    ], $overrides));
}

beforeEach(fn () => Category::create(['name' => 'News', 'slug' => 'news']));

test('news:fetch imports relevant items as drafts and skips irrelevant ones', function () {
    Http::fake(['*' => Http::response(rssFixture())]);
    $source = activeSource();

    $this->artisan('news:fetch')->assertSuccessful();

    $articles = Article::orderBy('title')->get();
    expect($articles)->toHaveCount(2)
        ->and($articles->pluck('status')->unique()->values()->all())->toBe(['draft'])
        ->and($articles->pluck('source_id')->unique()->values()->all())->toBe([$source->id]);

    $fog = Article::where('source_url', 'https://example.com/fog-tagaytay')->firstOrFail();
    expect($fog->body)->toContain('Via [Test Outlet](https://example.com/fog-tagaytay)')
        ->and($fog->author)->toBe('Test Outlet')
        ->and($fog->published_at->toDateTimeString())->toBe('2026-08-03 08:00:00');

    $source->refresh();
    expect($source->last_fetched_at)->not->toBeNull();
});

test('news:fetch dedupes by source_url on repeat runs', function () {
    Http::fake(['*' => Http::response(rssFixture())]);
    activeSource();

    $this->artisan('news:fetch')->assertSuccessful();
    $this->artisan('news:fetch')->assertSuccessful();

    expect(Article::count())->toBe(2);
});

test('news:fetch guesses category from keywords', function () {
    Http::fake(['*' => Http::response(rssFixture())]);
    Category::create(['name' => 'Taal Volcano', 'slug' => 'taal-volcano']);
    Category::create(['name' => 'Weather', 'slug' => 'weather']);
    activeSource();

    $this->artisan('news:fetch')->assertSuccessful();

    expect(Article::where('source_url', 'https://example.com/taal-activity')->firstOrFail()->category->name)->toBe('Taal Volcano')
        ->and(Article::where('source_url', 'https://example.com/fog-tagaytay')->firstOrFail()->category->name)->toBe('Weather');
});

test('news:fetch skips inactive sources and logs failed feeds', function () {
    Http::fake(['*' => Http::response('Server Error', 500)]);
    activeSource(['is_active' => false]);

    $this->artisan('news:fetch')->assertSuccessful();
    expect(Article::count())->toBe(0);

    Source::firstOrFail()->update(['is_active' => true]);
    $this->artisan('news:fetch')->assertSuccessful();
    expect(Article::count())->toBe(0);
});
