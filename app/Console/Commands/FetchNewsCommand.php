<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch {--source= : Poll only the given source ID}';

    protected $description = 'Poll active source RSS feeds and queue relevant items as draft articles';

    public function handle(): int
    {
        $sources = Source::where('is_active', true)
            ->whereNotNull('feed_url')
            ->when($this->option('source'), fn ($query, $id) => $query->where('id', $id))
            ->get();

        if ($sources->isEmpty()) {
            $this->warn('No active sources with feed URLs found.');

            return self::SUCCESS;
        }

        $totals = ['created' => 0, 'duplicates' => 0, 'irrelevant' => 0, 'failed' => 0];

        foreach ($sources as $source) {
            $result = $this->poll($source);
            foreach ($totals as $key => $_) {
                $totals[$key] += $result[$key];
            }
        }

        $summary = sprintf(
            'news:fetch done — %d new drafts, %d duplicates, %d irrelevant, %d failed feeds',
            $totals['created'], $totals['duplicates'], $totals['irrelevant'], $totals['failed'],
        );

        $this->info($summary);
        Log::info($summary);

        return self::SUCCESS;
    }

    /**
     * @return array{created: int, duplicates: int, irrelevant: int, failed: int}
     */
    private function poll(Source $source): array
    {
        $counts = ['created' => 0, 'duplicates' => 0, 'irrelevant' => 0, 'failed' => 0];

        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => config('newsroom.user_agent')])
                ->get($source->feed_url);
        } catch (\Throwable $e) {
            Log::error('Feed fetch failed', ['source' => $source->name, 'feed_url' => $source->feed_url, 'error' => $e->getMessage()]);
            $this->error("{$source->name}: fetch failed ({$e->getMessage()})");
            $counts['failed']++;

            return $counts;
        }

        if (! $response->ok()) {
            Log::error('Feed returned non-200', ['source' => $source->name, 'feed_url' => $source->feed_url, 'status' => $response->status()]);
            $this->error("{$source->name}: HTTP {$response->status()}");
            $counts['failed']++;

            return $counts;
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($response->body());
        libxml_use_internal_errors($previous);

        if ($xml === false) {
            Log::error('Feed XML parse failed', ['source' => $source->name, 'feed_url' => $source->feed_url]);
            $this->error("{$source->name}: invalid XML");
            $counts['failed']++;

            return $counts;
        }

        foreach ($xml->channel->item ?? [] as $item) {
            $outcome = $this->importItem($source, $item);
            if ($outcome !== null) {
                $counts[$outcome]++;
            }
        }

        $source->update(['last_fetched_at' => now()]);
        $this->line(sprintf(
            '%s: %d new, %d dupes, %d irrelevant',
            $source->name, $counts['created'], $counts['duplicates'], $counts['irrelevant'],
        ));

        return $counts;
    }

    /**
     * @return 'created'|'duplicates'|'irrelevant'|null
     */
    private function importItem(Source $source, \SimpleXMLElement $item): ?string
    {
        $title = trim((string) $item->title);
        $link = trim((string) $item->link);
        $description = trim(preg_replace('/\s+/', ' ', strip_tags((string) $item->description)) ?? '');

        if ($title === '' || $link === '') {
            return null;
        }

        if (! $this->isRelevant($title.' '.$description)) {
            return 'irrelevant';
        }

        if (Article::where('source_url', $link)->exists()) {
            return 'duplicates';
        }

        try {
            $publishedAt = Carbon::parse((string) $item->pubDate);
        } catch (\Throwable) {
            $publishedAt = now();
        }

        $body = ($description !== '' ? $description : $title)
            ."\n\n---\n\n*Via [{$source->name}]({$link}). Imported automatically — pending editorial review and rewrite before publication.*";

        try {
            Article::create([
                'title' => $title,
                'excerpt' => Str::limit($description !== '' ? $description : $title, 200),
                'body' => $body,
                'category_id' => $this->guessCategoryId($title.' '.$description),
                'source_id' => $source->id,
                'source_url' => $link,
                'author' => $source->name,
                'status' => Article::STATUS_DRAFT,
                'published_at' => $publishedAt,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to import feed item', ['source' => $source->name, 'url' => $link, 'error' => $e->getMessage()]);

            return null;
        }

        return 'created';
    }

    private function isRelevant(string $text): bool
    {
        $text = mb_strtolower($text);

        foreach (config('newsroom.relevance_keywords', []) as $keyword) {
            if (str_contains($text, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function guessCategoryId(string $text): int
    {
        $text = mb_strtolower($text);
        $name = config('newsroom.fallback_category', 'News');

        foreach (config('newsroom.category_keywords', []) as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $name = $category;
                    break 2;
                }
            }
        }

        $categoryModel = Category::where('name', $name)->first()
            ?? Category::where('name', config('newsroom.fallback_category', 'News'))->first()
            ?? Category::first();

        if ($categoryModel === null) {
            throw new \RuntimeException('No categories exist — run CategorySeeder first.');
        }

        return $categoryModel->id;
    }
}
