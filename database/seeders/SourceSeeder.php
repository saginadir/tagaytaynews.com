<?php

namespace Database\Seeders;

use App\Models\Source;
use Illuminate\Database\Seeder;

class SourceSeeder extends Seeder
{
    /**
     * Curated source list with verification tiers (see MISSION.md).
     * RSS URLs verified 2026-08-02: Tier 2 feeds return valid RSS 2.0.
     * Tier 1 officials publish no RSS — kept inactive until scrapers exist.
     */
    public function run(): void
    {
        $sources = [
            // Tier 1 — official. No RSS available; monitored manually for now.
            ['name' => 'PHIVOLCS', 'url' => 'https://www.phivolcs.dost.gov.ph', 'tier' => 1,
                'notes' => 'Taal Volcano bulletins. No RSS feed; check bulletin page manually or build a scraper.'],
            ['name' => 'PAGASA', 'url' => 'https://www.pagasa.dost.gov.ph', 'tier' => 1,
                'notes' => 'Weather advisories and tropical cyclone bulletins. No RSS feed.'],
            ['name' => 'Tagaytay City Government', 'url' => 'https://www.tagaytaycity.gov.ph', 'tier' => 1,
                'notes' => 'Official site often unreachable; primary channel is Facebook — verify handle before relying on it.'],
            ['name' => 'Cavite Provincial Government', 'url' => 'https://cavite.gov.ph', 'tier' => 1,
                'notes' => 'Provincial announcements. No RSS feed found.'],

            // Tier 2 — established media with working RSS (verified 2026-08-02).
            ['name' => 'Inquirer', 'url' => 'https://www.inquirer.net', 'feed_url' => 'https://newsinfo.inquirer.net/feed', 'tier' => 2, 'is_active' => true],
            ['name' => 'The Philippine Star', 'url' => 'https://www.philstar.com', 'feed_url' => 'https://www.philstar.com/rss/headlines', 'tier' => 2, 'is_active' => true],
            ['name' => 'Philstar Nation', 'url' => 'https://www.philstar.com/nation', 'feed_url' => 'https://www.philstar.com/rss/nation', 'tier' => 2, 'is_active' => true],
            ['name' => 'Rappler', 'url' => 'https://www.rappler.com', 'feed_url' => 'https://www.rappler.com/feed/', 'tier' => 2, 'is_active' => true],
            ['name' => 'Interaksyon', 'url' => 'https://interaksyon.philstar.com', 'feed_url' => 'https://interaksyon.philstar.com/feed/', 'tier' => 2, 'is_active' => true],
            ['name' => 'BusinessWorld', 'url' => 'https://www.bworldonline.com', 'feed_url' => 'https://www.bworldonline.com/feed/', 'tier' => 2, 'is_active' => true],
            ['name' => 'SunStar', 'url' => 'https://www.sunstar.com.ph', 'feed_url' => 'https://www.sunstar.com.ph/feed', 'tier' => 2, 'is_active' => true],

            // Checked but unusable: Manila Bulletin & Manila Standard (feeds return HTML),
            // GMA News (RSS retired), ABS-CBN & PNA (bot-blocked). Revisit manually.
        ];

        foreach ($sources as $source) {
            Source::firstOrCreate(['name' => $source['name']], $source);
        }
    }
}
