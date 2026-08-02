<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Category;
use App\Models\Source;
use App\Support\RawHttp;
use Illuminate\Console\Command;
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
        $seen = [];

        foreach ($matches as [$full, $url, $title]) {
            $url = rtrim($url, '/').'/';
            $title = trim($title);

            if (isset($seen[$url])) {
                continue;
            }
            $seen[$url] = true;

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

        $this->info("PHIVOLCS watch: {$created} new bulletin(s) queued, ".count($seen).' link(s) checked.');
        Log::info('news:watch-phivolcs done', ['created' => $created, 'checked' => count($seen)]);

        return self::SUCCESS;
    }
}
