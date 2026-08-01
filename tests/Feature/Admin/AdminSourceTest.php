<?php

use App\Models\Article;
use App\Models\Source;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin source routes require admin authentication', function () {
    $source = Source::create(['name' => 'Blog', 'url' => 'https://blog.example.com', 'tier' => 3]);

    $this->get(route('admin.sources.index'))->assertRedirect(route('admin.login'));
    $this->post(route('admin.sources.store'), [])->assertRedirect(route('admin.login'));
    $this->put(route('admin.sources.update', $source), [])->assertRedirect(route('admin.login'));
    $this->delete(route('admin.sources.destroy', $source))->assertRedirect(route('admin.login'));
});

test('admin can view the source list', function () {
    Source::create(['name' => 'PHIVOLCS', 'url' => 'https://phivolcs.dost.gov.ph', 'tier' => 1]);

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.sources.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Sources')->has('sources', 1));
});

test('admin can create a source', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.sources.store'), [
            'name' => 'PHIVOLCS',
            'url' => 'https://phivolcs.dost.gov.ph',
            'tier' => 1,
        ])
        ->assertRedirect(route('admin.sources.index'));

    $this->assertDatabaseHas('sources', ['name' => 'PHIVOLCS', 'tier' => 1]);
});

test('source validation requires name url and a tier between 1 and 3', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.sources.store'), ['tier' => 9])
        ->assertSessionHasErrors(['name', 'url', 'tier']);
});

test('admin can update a source', function () {
    $source = Source::create(['name' => 'Blog', 'url' => 'https://blog.example.com', 'tier' => 3]);

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.sources.update', $source), [
            'name' => 'Blog',
            'url' => 'https://blog.example.com',
            'tier' => 2,
            'notes' => 'Upgraded after verification',
        ])
        ->assertRedirect(route('admin.sources.index'));

    $this->assertDatabaseHas('sources', ['id' => $source->id, 'tier' => 2, 'notes' => 'Upgraded after verification']);
});

test('admin can delete a source and articles keep a null source reference', function () {
    $source = Source::create(['name' => 'Blog', 'url' => 'https://blog.example.com', 'tier' => 3]);
    $article = Article::factory()->create(['source_id' => $source->id]);

    $this->withSession(['admin_authenticated' => true])
        ->delete(route('admin.sources.destroy', $source))
        ->assertRedirect(route('admin.sources.index'));

    $this->assertDatabaseMissing('sources', ['id' => $source->id]);
    expect($article->refresh()->source_id)->toBeNull();
});
