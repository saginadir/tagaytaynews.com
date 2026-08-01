<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('sitemap.xml lists static pages, categories, and published articles', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    Article::factory()->published()->create(['category_id' => $category->id, 'slug' => 'ridge-story']);
    Article::factory()->create(['category_id' => $category->id, 'slug' => 'hidden-draft']);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/xml');
    $response->assertSee('<loc>http://localhost/news/ridge-story</loc>', false);
    $response->assertDontSee('hidden-draft', false);
    $response->assertSee('<loc>http://localhost/news</loc>', false);
});

test('feed.xml serves an RSS feed of published articles', function () {
    $category = Category::factory()->create(['slug' => 'news', 'name' => 'News']);
    Article::factory()->published()->create([
        'category_id' => $category->id,
        'slug' => 'fog-report',
        'title' => 'Fog report for the ridge',
    ]);

    $response = $this->get('/feed.xml');

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toContain('application/rss+xml');
    $response->assertSee('Fog report for the ridge', false);
    $response->assertSee('<rss version="2.0">', false);
});

test('robots.txt allows crawling and points at the sitemap', function () {
    $this->get('/robots.txt')
        ->assertOk()
        ->assertSee('User-agent: *', false)
        ->assertSee('Sitemap: http://localhost/sitemap.xml', false);
});

test('llms.txt describes the site for AI crawlers', function () {
    Category::factory()->create(['name' => 'Weather']);

    $this->get('/llms.txt')
        ->assertOk()
        ->assertSee('# Tagaytay News', false)
        ->assertSee('Weather', false);
});
