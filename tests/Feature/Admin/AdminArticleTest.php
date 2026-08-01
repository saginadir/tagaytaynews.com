<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function validArticlePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Taal Volcano alert raised',
        'body' => "## Developing\n\nPHIVOLCS raised the alert level.",
        'category_id' => Category::factory()->create()->id,
        'status' => 'draft',
    ], $overrides);
}

test('admin article routes require admin authentication', function () {
    $article = Article::factory()->create();

    $this->get(route('admin.articles.index'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.articles.create'))->assertRedirect(route('admin.login'));
    $this->get(route('admin.articles.edit', $article))->assertRedirect(route('admin.login'));
    $this->post(route('admin.articles.store'), [])->assertRedirect(route('admin.login'));
    $this->put(route('admin.articles.update', $article), [])->assertRedirect(route('admin.login'));
    $this->delete(route('admin.articles.destroy', $article))->assertRedirect(route('admin.login'));
});

test('admin can view the article list', function () {
    Article::factory()->count(2)->create();

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.articles.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('admin/articles/Index')->has('articles', 2));
});

test('admin can create an article as draft', function () {
    $response = $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload([
            'title' => 'Fog advisory on the ridge',
        ]));

    $response->assertRedirect(route('admin.articles.index'));

    $article = Article::first();
    expect($article->title)->toBe('Fog advisory on the ridge')
        ->and($article->slug)->toBe('fog-advisory-on-the-ridge')
        ->and($article->status)->toBe('draft')
        ->and($article->published_at)->toBeNull()
        ->and($article->author)->toBe('Tagaytay News Staff');
});

test('admin can publish an article which sets published_at', function () {
    $article = Article::factory()->create();

    $response = $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.articles.update', $article), validArticlePayload([
            'category_id' => $article->category_id,
            'status' => 'published',
            'published_at' => '2026-08-01T10:00',
        ]));

    $response->assertRedirect(route('admin.articles.index'));

    $article->refresh();
    expect($article->status)->toBe('published')
        ->and($article->published_at)->not->toBeNull()
        ->and($article->published_at->format('Y-m-d H:i'))->toBe('2026-08-01 10:00');
});

test('publishing requires published_at', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload(['status' => 'published']))
        ->assertSessionHasErrors('published_at');
});

test('article validation requires title body category and status', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), [])
        ->assertSessionHasErrors(['title', 'body', 'category_id', 'status']);
});

test('duplicate titles get unique slugs', function () {
    $category = Category::factory()->create();

    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload(['title' => 'Weekend traffic', 'category_id' => $category->id]));
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload(['title' => 'Weekend traffic', 'category_id' => $category->id]));

    expect(Article::orderBy('id')->pluck('slug')->all())->toBe(['weekend-traffic', 'weekend-traffic-2']);
});

test('manual slug is respected and must be unique', function () {
    Article::factory()->create(['slug' => 'taken']);

    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload(['slug' => 'taken']))
        ->assertSessionHasErrors('slug');

    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload(['slug' => 'custom-slug']))
        ->assertRedirect(route('admin.articles.index'));

    expect(Article::orderBy('id')->get()->last()->slug)->toBe('custom-slug');
});

test('source url must be a valid url', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.articles.store'), validArticlePayload(['source_url' => 'not-a-url']))
        ->assertSessionHasErrors('source_url');
});

test('admin can delete an article', function () {
    $article = Article::factory()->create();

    $this->withSession(['admin_authenticated' => true])
        ->delete(route('admin.articles.destroy', $article))
        ->assertRedirect(route('admin.articles.index'));

    $this->assertDatabaseMissing('articles', ['id' => $article->id]);
});
