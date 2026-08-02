<?php

use App\Models\Article;
use App\Models\Category;
use App\Models\PageView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('homepage trending ranks articles by page views and hides when quiet', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    $popular = Article::factory()->published()->create(['category_id' => $category->id, 'slug' => 'popular-story']);
    $runnerUp = Article::factory()->published()->create(['category_id' => $category->id, 'slug' => 'runner-up-story']);
    $wallflower = Article::factory()->published()->create(['category_id' => $category->id, 'slug' => 'wallflower-story']);

    PageView::create(['path' => '/news/popular-story']);
    PageView::create(['path' => '/news/popular-story']);
    PageView::create(['path' => '/news/popular-story']);
    PageView::create(['path' => '/news/runner-up-story']);
    PageView::create(['path' => '/']); // non-article path is ignored

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('trending.0.id', $popular->id)
            ->where('trending.1.id', $runnerUp->id)
            ->has('trending', 2));

    expect($wallflower->wasRecentlyCreated)->toBeTrue(); // sanity: never viewed, never trending
});

test('homepage trending is empty when fewer than two articles have views', function () {
    $category = Category::factory()->create(['slug' => 'news']);
    Article::factory()->published()->create(['category_id' => $category->id, 'slug' => 'lonely-story']);

    PageView::create(['path' => '/news/lonely-story']);

    $this->get('/')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('trending', 0));
});
