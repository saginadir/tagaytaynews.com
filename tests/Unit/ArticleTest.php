<?php

use App\Models\Article;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('slug is generated from the title on create', function () {
    $article = Article::factory()->create(['title' => 'Taal Volcano Eruption Update']);

    expect($article->slug)->toBe('taal-volcano-eruption-update');
});

test('slug collisions get an incrementing suffix', function () {
    Article::factory()->create(['title' => 'Weekend Traffic']);
    $second = Article::factory()->create(['title' => 'Weekend Traffic']);
    $third = Article::factory()->create(['title' => 'Weekend Traffic']);

    expect($second->slug)->toBe('weekend-traffic-2')
        ->and($third->slug)->toBe('weekend-traffic-3');
});

test('a manually set slug is kept as given', function () {
    $article = Article::factory()->create(['title' => 'Some Title', 'slug' => 'my-custom-slug']);

    expect($article->slug)->toBe('my-custom-slug');
});

test('published scope only returns live articles, latest first', function () {
    Article::factory()->create(); // draft, no published_at
    Article::factory()->published()->create(['published_at' => now()->addDay()]); // scheduled future
    $older = Article::factory()->published()->create(['published_at' => now()->subDays(2)]);
    $newer = Article::factory()->published()->create(['published_at' => now()->subHour()]);

    $results = Article::published()->get();

    expect($results->pluck('id')->all())->toBe([$newer->id, $older->id]);
});
