<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Imports evergreen articles from content/articles/*.md (front matter + markdown body)
 * so original content is version-controlled and deployable to production.
 */
class ImportArticlesCommand extends Command
{
    protected $signature = 'articles:import {--dir= : Override the content directory}';

    protected $description = 'Import or update published articles from content/articles/*.md';

    public function handle(): int
    {
        $directory = $this->option('dir') ?: base_path('content/articles');

        if (! is_dir($directory)) {
            $this->warn("No content directory at {$directory}");

            return self::SUCCESS;
        }

        $imported = 0;

        foreach (glob($directory.'/*.md') ?: [] as $path) {
            try {
                $this->importFile($path);
                $imported++;
            } catch (\Throwable $e) {
                Log::error('Article import failed', ['file' => $path, 'error' => $e->getMessage()]);
                $this->error(basename($path).": {$e->getMessage()}");
            }
        }

        $this->info("Imported {$imported} article(s).");
        Log::info("articles:import imported {$imported} article(s).");

        return self::SUCCESS;
    }

    private function importFile(string $path): void
    {
        $raw = file_get_contents($path);

        if ($raw === false || ! preg_match('/\A---\s*\n(.*?)\n---\s*\n?(.*)\z/s', $raw, $matches)) {
            throw new \RuntimeException('missing or malformed front matter');
        }

        $meta = $this->parseFrontMatter($matches[1]);
        $body = trim($matches[2]);

        foreach (['title', 'slug', 'category'] as $required) {
            if (empty($meta[$required])) {
                throw new \RuntimeException("front matter missing '{$required}'");
            }
        }

        if ($body === '') {
            throw new \RuntimeException('empty body');
        }

        $category = Category::where('name', $meta['category'])->first();

        if ($category === null) {
            throw new \RuntimeException("unknown category '{$meta['category']}'");
        }

        $article = Article::firstOrNew(['slug' => $meta['slug']]);
        $article->fill([
            'title' => $meta['title'],
            'excerpt' => $meta['excerpt'] ?? null,
            'body' => $body,
            'category_id' => $category->id,
            'author' => $meta['author'] ?? 'Tagaytay News Staff',
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => isset($meta['published_at'])
                ? Carbon::parse($meta['published_at'])
                : ($article->published_at ?? now()),
            'seo_title' => $meta['seo_title'] ?? null,
            'seo_description' => $meta['seo_description'] ?? null,
        ]);
        $article->save();

        $this->line("{$meta['slug']}: ".($article->wasRecentlyCreated ? 'created' : 'updated'));
    }

    /**
     * @return array<string, string>
     */
    private function parseFrontMatter(string $frontMatter): array
    {
        $meta = [];

        foreach (preg_split('/\R/', $frontMatter) ?: [] as $line) {
            if (preg_match('/^([a-z_]+):\s*(.*)$/', trim($line), $kv)) {
                $meta[$kv[1]] = trim($kv[2]);
            }
        }

        return $meta;
    }
}
