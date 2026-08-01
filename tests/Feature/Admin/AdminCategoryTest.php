<?php

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin category routes require admin authentication', function () {
    $category = Category::factory()->create();

    $this->get(route('admin.categories.index'))->assertRedirect(route('admin.login'));
    $this->post(route('admin.categories.store'), [])->assertRedirect(route('admin.login'));
    $this->put(route('admin.categories.update', $category), [])->assertRedirect(route('admin.login'));
    $this->delete(route('admin.categories.destroy', $category))->assertRedirect(route('admin.login'));
});

test('admin can view the category list', function () {
    Category::factory()->count(2)->create();

    $this->withSession(['admin_authenticated' => true])
        ->get(route('admin.categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/Categories')->has('categories', 2));
});

test('admin can create a category and the slug is generated', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.categories.store'), ['name' => 'Food & Drink'])
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', ['name' => 'Food & Drink', 'slug' => 'food-drink']);
});

test('category name is required', function () {
    $this->withSession(['admin_authenticated' => true])
        ->post(route('admin.categories.store'), [])
        ->assertSessionHasErrors('name');
});

test('admin can update a category', function () {
    $category = Category::factory()->create(['name' => 'Old Name']);

    $this->withSession(['admin_authenticated' => true])
        ->put(route('admin.categories.update', $category), ['name' => 'New Name', 'description' => 'Updated'])
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Name', 'description' => 'Updated']);
});

test('admin can delete an empty category', function () {
    $category = Category::factory()->create();

    $this->withSession(['admin_authenticated' => true])
        ->delete(route('admin.categories.destroy', $category))
        ->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('a category that still has articles cannot be deleted', function () {
    $category = Category::factory()->create();
    Article::factory()->create(['category_id' => $category->id]);

    $this->withSession(['admin_authenticated' => true])
        ->delete(route('admin.categories.destroy', $category))
        ->assertSessionHasErrors('message');

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});
