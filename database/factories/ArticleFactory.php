<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(6),
            'excerpt' => fake()->optional()->paragraph(),
            'body' => fake()->paragraphs(3, true),
            'category_id' => Category::factory(),
            'author' => 'Tagaytay News Staff',
            'status' => Article::STATUS_DRAFT,
            'published_at' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn () => [
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now()->subHour(),
        ]);
    }
}
