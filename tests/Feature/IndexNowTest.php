<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.indexnow.enabled' => true]);
    Category::create(['name' => 'News', 'slug' => 'news']);
});

test('indexnow key is generated once, persisted, and served from the key file', function () {
    $key = \App\Support\IndexNow::key();

    expect($key)->toMatch('/^[a-z0-9]{32}$/')
        ->and(\App\Support\IndexNow::key())->toBe($key); // stable across calls

    $this->get("/{$key}.txt")
        ->assertOk()
        ->assertSee($key, false);

    $this->get('/'.str_repeat('9', 32).'.txt')->assertNotFound();
});

test('publishing an article pings indexnow with its URL', function () {
    Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

    Article::create([
        'title' => 'Fresh story',
        'body' => 'body',
        'category_id' => Category::firstOrFail()->id,
        'status' => 'published',
        'published_at' => now(),
    ]);

    Http::assertSent(fn ($request) => str_contains($request->url(), 'api.indexnow.org')
        && str_contains((string) $request['urlList'][0], '/news/fresh-story'));
});

test('drafts and non-status edits do not ping indexnow', function () {
    Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);

    $article = Article::create([
        'title' => 'Draft story',
        'body' => 'body',
        'category_id' => Category::firstOrFail()->id,
        'status' => 'draft',
    ]);

    $article->update(['title' => 'Draft story retitled']);

    Http::assertNothingSent();

    $article->update(['status' => 'published', 'published_at' => now()]);

    Http::assertSentCount(1);
});

test('seo:indexnow submits all public urls', function () {
    Http::fake(['api.indexnow.org/*' => Http::response('', 200)]);
    Article::create([
        'title' => 'Live one',
        'body' => 'body',
        'category_id' => Category::firstOrFail()->id,
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->artisan('seo:indexnow')->assertSuccessful();

    Http::assertSent(function ($request) {
        $urls = $request['urlList'];

        return count($urls) >= 7
            && in_array('http://localhost/quiz', $urls)
            && in_array('http://localhost/map', $urls)
            && in_array('http://localhost/news/live-one', $urls);
    });
});
