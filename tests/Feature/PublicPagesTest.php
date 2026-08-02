<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('home page renders hero, latest, sections, and shared nav categories', function () {
    $category = Category::factory()->create();
    Article::factory()->count(3)->published()->create(['category_id' => $category->id]);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Home')
            ->has('hero')
            ->has('latest', 2)
            ->has('sections', 1)
            ->has('sections.0.articles', 3)
            ->has('navCategories', 1)
            ->has('seo'));
});

test('home page falls back to the empty state when nothing is published', function () {
    Article::factory()->create(); // draft only

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Home')->where('hero', null));
});

test('published article renders and the HTML contains NewsArticle JSON-LD', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    Article::factory()->published()->create([
        'category_id' => $category->id,
        'slug' => 'foggy-morning-on-the-ridge',
        'title' => 'Foggy morning on the ridge',
    ]);

    $response = $this->get('/news/foggy-morning-on-the-ridge');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Article')
            ->where('article.title', 'Foggy morning on the ridge')
            ->has('bodyHtml')
            ->has('related'));
    $response->assertSee('application/ld+json', false);
    $response->assertSee('NewsArticle', false);
    $response->assertSee('canonical', false);
});

test('draft article returns 404', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    Article::factory()->create(['category_id' => $category->id, 'slug' => 'draft-story']);

    $this->get('/news/draft-story')->assertNotFound();
});

test('article scheduled for the future returns 404', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    Article::factory()->published()->create([
        'category_id' => $category->id,
        'slug' => 'future-story',
        'published_at' => now()->addDay(),
    ]);

    $this->get('/news/future-story')->assertNotFound();
});

test('article under the wrong category slug returns 404', function () {
    $news = Category::factory()->create(['slug' => 'news']);
    Category::factory()->create(['slug' => 'weather']);
    Article::factory()->published()->create(['category_id' => $news->id, 'slug' => 'foggy-morning']);

    $this->get('/weather/foggy-morning')->assertNotFound();
    $this->get('/news/foggy-morning')->assertOk();
});

test('category page renders published articles only', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    Article::factory()->published()->create(['category_id' => $category->id]);
    Article::factory()->create(['category_id' => $category->id]); // draft

    $this->get('/news')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Category')
            ->where('category.slug', 'news')
            ->has('articles', 1));
});

test('unknown category slug returns 404', function () {
    $this->get('/no-such-section')->assertNotFound();
});

test('about and contact pages render with nav categories shared', function () {
    Category::factory()->count(2)->create();

    $this->get('/about')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('About')->has('navCategories', 2));

    $this->get('/contact')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Contact')->has('navCategories', 2));
});

test('static routes win over the category wildcard', function () {
    Category::factory()->create(['slug' => 'about']);

    $this->get('/about')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('About'));
});

test('category page falls back to latest stories when the section is empty', function () {
    Category::factory()->create(['slug' => 'news', 'name' => 'News']);
    $weather = Category::factory()->create(['slug' => 'weather', 'name' => 'Weather']);
    Article::factory()->published()->create(['category_id' => $weather->id]);

    $this->get('/news')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Category')
            ->has('articles', 0)
            ->has('fallback', 1));

    // A section with stories shows no fallback.
    $this->get('/weather')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('articles', 1)
            ->has('fallback', 0));
});
