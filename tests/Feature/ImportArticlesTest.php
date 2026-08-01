<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function fixtureDir(array $files): string
{
    $dir = sys_get_temp_dir().'/articles-import-'.uniqid();
    mkdir($dir, 0755, true);
    foreach ($files as $name => $contents) {
        file_put_contents($dir.'/'.$name, $contents);
    }

    return $dir;
}

const VALID_ARTICLE = <<<'MD'
---
title: Taal Volcano: What You Need to Know
slug: taal-volcano-guide
category: Taal Volcano
excerpt: A plain-language guide to Taal Volcano.
seo_title: Taal Volcano Guide
seo_description: Everything about Taal Volcano for Tagaytay visitors and residents.
published_at: 2026-08-02
---

Taal Volcano sits on an island in a lake.

## Alert levels

PHIVOLCS issues the bulletins.
MD;

beforeEach(fn () => Category::create(['name' => 'Taal Volcano', 'slug' => 'taal-volcano']));

test('articles:import creates published articles from markdown files', function () {
    $dir = fixtureDir(['taal-volcano-guide.md' => VALID_ARTICLE]);

    $this->artisan('articles:import', ['--dir' => $dir])->assertSuccessful();

    $article = Article::where('slug', 'taal-volcano-guide')->firstOrFail();
    expect($article->status)->toBe('published')
        ->and($article->category->name)->toBe('Taal Volcano')
        ->and($article->seo_title)->toBe('Taal Volcano Guide')
        ->and($article->published_at->toDateString())->toBe('2026-08-02')
        ->and($article->body)->toContain('## Alert levels');
});

test('articles:import updates existing articles by slug', function () {
    $dir = fixtureDir(['taal-volcano-guide.md' => VALID_ARTICLE]);

    $this->artisan('articles:import', ['--dir' => $dir])->assertSuccessful();

    file_put_contents($dir.'/taal-volcano-guide.md', str_replace(
        'What You Need to Know', 'An Updated Guide', VALID_ARTICLE
    ));

    $this->artisan('articles:import', ['--dir' => $dir])->assertSuccessful();

    expect(Article::count())->toBe(1)
        ->and(Article::firstOrFail()->title)->toBe('Taal Volcano: An Updated Guide');
});

test('articles:import rejects malformed files but continues with others', function () {
    $dir = fixtureDir([
        'broken.md' => 'no front matter here',
        'unknown-category.md' => str_replace('category: Taal Volcano', 'category: Sports', VALID_ARTICLE),
        'taal-volcano-guide.md' => VALID_ARTICLE,
    ]);

    $this->artisan('articles:import', ['--dir' => $dir])->assertSuccessful();

    expect(Article::count())->toBe(1);
});
