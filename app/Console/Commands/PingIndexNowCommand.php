<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Support\IndexNow;
use Illuminate\Console\Command;

/**
 * Pings every public URL to IndexNow (Bing/Yandex instant indexing).
 * Individual publishes ping instantly via the Article observer; this is
 * the daily full-sweep safety net.
 */
class PingIndexNowCommand extends Command
{
    protected $signature = 'seo:indexnow';

    protected $description = 'Submit all public URLs to IndexNow';

    public function handle(): int
    {
        $urls = [
            route('home'),
            route('about'),
            route('contact'),
            route('work-with-us'),
            route('quiz'),
            route('map'),
        ];

        foreach (Category::all() as $category) {
            $urls[] = route('category.show', $category);
        }

        foreach (Article::published()->with('category')->get() as $article) {
            $urls[] = route('article.show', [$article->category, $article]);
        }

        $ok = IndexNow::ping($urls);

        $this->{$ok ? 'info' : 'error'}('IndexNow: submitted '.count($urls).' URL(s).');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
