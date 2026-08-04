<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds a properly licensed photo on Wikimedia Commons for an article and
 * imports it (via media:import). Editorial rule: no article ships text-only —
 * this is the fast path to a relevant, credited image.
 */
class FindMediaCommand extends Command
{
    protected $signature = 'media:find {query : Commons search query}
                            {--article= : Attach to the article with this slug}
                            {--alt= : Alt text (defaults to the Commons title)}
                            {--pick=0 : Candidate index to import (0 = best)}
                            {--list : List candidates without importing}';

    protected $description = 'Search Wikimedia Commons for a licensed photo and import it';

    private const LICENSE_ALLOWLIST = [
        'cc0', 'cc zero', 'public domain', 'cc by 2.0', 'cc by 2.5', 'cc by 3.0',
        'cc by 4.0', 'cc by-sa 2.0', 'cc by-sa 2.5', 'cc by-sa 3.0', 'cc by-sa 4.0',
        'attribution', 'cc-by-sa-4.0', 'cc-by-4.0',
    ];

    public function handle(): int
    {
        try {
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'tagaytaynews-dev/1.0 (contact: hello@tagaytaynews.com)'])
                ->get('https://commons.wikimedia.org/w/api.php', [
                    'action' => 'query',
                    'format' => 'json',
                    'generator' => 'search',
                    'gsrsearch' => $this->argument('query'),
                    'gsrnamespace' => 6,
                    'gsrlimit' => 15,
                    'prop' => 'imageinfo',
                    'iiprop' => 'url|size|extmetadata',
                ]);
        } catch (\Throwable $e) {
            Log::error('Commons search failed', ['query' => $this->argument('query'), 'error' => $e->getMessage()]);
            $this->error("Search failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        $candidates = collect($response->json('query.pages') ?? [])
            ->map(function (array $page): ?array {
                $info = $page['imageinfo'][0] ?? [];
                $meta = $info['extmetadata'] ?? [];
                $license = mb_strtolower(trim(strip_tags($meta['LicenseShortName']['value'] ?? '')));
                $width = (int) ($info['width'] ?? 0);
                $height = (int) ($info['height'] ?? 0);

                if ($width < 1200 || $width <= $height || ! in_array($license, self::LICENSE_ALLOWLIST, true)) {
                    return null;
                }

                return [
                    'url' => strtok($info['url'] ?? '', '?'),
                    'title' => $page['title'] ?? '',
                    'width' => $width,
                    'license' => $meta['LicenseShortName']['value'] ?? '?',
                    'artist' => trim(mb_substr(strip_tags($meta['Artist']['value'] ?? 'Wikimedia Commons contributor'), 0, 60)),
                    'score' => $width,
                ];
            })
            ->filter()
            ->sortByDesc('score')
            ->values();

        if ($candidates->isEmpty()) {
            $this->warn('No suitable licensed landscape images found — try a broader query.');

            return self::FAILURE;
        }

        foreach ($candidates->take(5) as $index => $candidate) {
            $this->line("  [{$index}] {$candidate['width']}px {$candidate['license']} — {$candidate['title']}");
        }

        if ($this->option('list')) {
            return self::SUCCESS;
        }

        $pick = (int) $this->option('pick');
        $chosen = $candidates->get($pick) ?? $candidates->first();
        $alt = $this->option('alt') ?: $chosen['title'];
        $credit = "{$chosen['artist']}, {$chosen['license']}, via Wikimedia Commons";

        $this->info("Importing: {$chosen['url']}");

        return Artisan::call('media:import', array_filter([
            'url' => $chosen['url'],
            '--alt' => $alt,
            '--credit' => $credit,
            '--article' => $this->option('article'),
        ])) === 0 ? self::SUCCESS : self::FAILURE;
    }
}
