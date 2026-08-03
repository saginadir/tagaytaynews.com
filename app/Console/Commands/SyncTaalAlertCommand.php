<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Support\RawHttp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Best-effort automatic Taal alert-level sync. PHIVOLCS publishes the level
 * inside image-based bulletins, so there is no guaranteed text source: this
 * job reads the newest Taal bulletin and updates the setting ONLY when it
 * finds an explicit "Alert Level N" in text. Parse failure never changes
 * anything — a wrong automatic level is worse than a stale manual one.
 */
class SyncTaalAlertCommand extends Command
{
    protected $signature = 'taal:sync-alert';

    protected $description = 'Update taal_alert_level from the newest PHIVOLCS Taal bulletin when text-parseable';

    private const INDEX_URL = 'https://www.phivolcs.dost.gov.ph/volcano-bulletin/';

    public function __construct(private readonly RawHttp $http)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $index = $this->http->get(self::INDEX_URL);

        if ($index === null) {
            $this->warn('Could not fetch PHIVOLCS bulletin index.');

            return self::SUCCESS; // transient — not an error state
        }

        // Newest Taal bulletin link on the index (first match in document order).
        if (! preg_match('/href="(https:\/\/www\.phivolcs\.dost\.gov\.ph\/[a-z0-9-]*taal[a-z0-9-]*)\/?"/i', $index, $match)) {
            Log::warning('taal:sync-alert found no Taal bulletin link — index structure may have changed');
            $this->warn('No Taal bulletin link found.');

            return self::SUCCESS;
        }

        $bulletin = $this->http->get(rtrim($match[1], '/').'/');

        if ($bulletin === null) {
            $this->warn('Could not fetch the newest Taal bulletin.');

            return self::SUCCESS;
        }

        $text = preg_replace('/\s+/', ' ', strip_tags($bulletin)) ?? '';

        if (! preg_match('/alert level\s*(\d)/i', $text, $levelMatch)) {
            $this->line('No text alert level in the newest bulletin (image-based) — leaving the setting untouched.');

            return self::SUCCESS;
        }

        $level = (int) $levelMatch[1];

        if ($level < 0 || $level > 5) {
            Log::warning('taal:sync-alert parsed an implausible level', ['level' => $level]);

            return self::SUCCESS;
        }

        $current = (int) Setting::get('taal_alert_level', '1');

        if ($level === $current) {
            $this->line("Alert level already {$level} — nothing to do.");

            return self::SUCCESS;
        }

        Setting::set('taal_alert_level', (string) $level);
        Log::info('Taal alert level auto-updated', ['from' => $current, 'to' => $level, 'bulletin' => $match[1]]);
        $this->info("Taal alert level updated: {$current} → {$level}");

        return self::SUCCESS;
    }
}
