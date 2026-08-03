<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Support\RawHttp;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Watches the PHIVOLCS volcano bulletin index for NEW Taal bulletins.
 * Their site is stale and bulletin bodies are image-based, so this is a
 * change detector: a new bulletin link queues a draft telling the editor
 * to write it up — it never auto-publishes.
 */
class WatchPhivolcsCommand extends Command
{
    protected $signature = 'news:watch-phivolcs';

    protected $description = 'Detect new PHIVOLCS Taal bulletins and queue drafts for the editor';

    private const INDEX_URL = 'https://www.phivolcs.dost.gov.ph/volcano-bulletin/';

    public function __construct(private readonly RawHttp $http)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $html = $this->http->get(self::INDEX_URL);

        if ($html === null) {
            $this->error('Could not fetch PHIVOLCS bulletin index.');

            return self::SUCCESS; // transient failure — not a command error
        }

        preg_match_all(
            '/href="(https:\/\/www\.phivolcs\.dost\.gov\.ph\/[a-z0-9-]*taal[a-z0-9-]*)\/?"[^>]*>([^<]{10,150})/i',
            $html,
            $matches,
            PREG_SET_ORDER,
        );

        if ($matches === []) {
            Log::warning('news:watch-phivolcs found no Taal links — page structure may have changed');
            $this->warn('No Taal bulletin links found on the index page.');

            return self::SUCCESS;
        }

        $source = Source::where('name', 'PHIVOLCS')->first();
        $category = Category::where('slug', 'taal-volcano')->first();

        if ($category === null) {
            $this->error('Taal Volcano category missing — run CategorySeeder.');

            return self::FAILURE;
        }

        $created = 0;
        $skippedStale = 0;
        $seen = [];

        foreach ($matches as [$full, $url, $title]) {
            $url = rtrim($url, '/').'/';
            $title = trim($title);

            if (isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;

            // Deleted drafts lose their dedupe key — never resurrect old
            // bulletins (the PHIVOLCS index is years stale itself).
            if ($this->isStale($url.' '.$title)) {
                $skippedStale++;

                continue;
            }

            if (Article::where('source_url', $url)->exists()) {
                continue;
            }

            try {
                Article::create([
                    'title' => "PHIVOLCS advisory to write up: {$title}",
                    'excerpt' => "A new PHIVOLCS Taal bulletin was posted: {$title}. Open the source link, verify the details, and rewrite before publishing.",
                    'body' => "A new Taal bulletin appeared on the PHIVOLCS volcano bulletin index:\n\n**{$title}**\n\n<{$url}>\n\n---\n\n*Detected automatically by news:watch-phivolcs. The bulletin body on the PHIVOLCS site is image-based — open the link, read the figures (alert level, SO₂, seismicity, vog), and rewrite this into a proper story before publishing.*",
                    'category_id' => $category->id,
                    'source_id' => $source?->id,
                    'source_url' => $url,
                    'author' => 'PHIVOLCS watch',
                    'status' => Article::STATUS_DRAFT,
                ]);
                $created++;
            } catch (\Throwable $e) {
                Log::error('Failed to queue PHIVOLCS bulletin draft', ['url' => $url, 'error' => $e->getMessage()]);
            }
        }

        $this->info("PHIVOLCS watch: {$created} new bulletin(s) queued, {$skippedStale} stale skipped, ".count($seen).' link(s) checked.');
        Log::info('news:watch-phivolcs done', ['created' => $created, 'stale' => $skippedStale, 'checked' => count($seen)]);

        return self::SUCCESS;
    }

    /**
     * Bulletin URLs carry an English or Filipino date slug
     * (...-09-july-2025-... / ...-09-hulyo-2025-...). Anything older than
     * 14 days is archival, not news. Unparseable dates pass through (safer
     * to alert the editor than to miss a fresh bulletin).
     */
    private function isStale(string $text): bool
    {
        $months = [
            'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4, 'may' => 5, 'june' => 6,
            'july' => 7, 'august' => 8, 'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
            'enero' => 1, 'pebrero' => 2, 'marzo' => 3, 'abril' => 4, 'mayo' => 5, 'hunyo' => 6,
            'hulyo' => 7, 'agosto' => 8, 'setyembre' => 9, 'oktubre' => 10, 'nobyembre' => 11, 'disyembre' => 12,
        ];

        if (! preg_match('/(\d{1,2})-([a-z]+)-(\d{4})/i', $text, $match)) {
            return false;
        }

        $month = $months[mb_strtolower($match[2])] ?? null;

        if ($month === null) {
            return false;
        }

        return Carbon::createFromDate((int) $match[3], $month, (int) $match[1])
            ->lt(now()->subDays(14));
    }
}
