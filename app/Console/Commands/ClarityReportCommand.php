<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pulls the Microsoft Clarity Data Export API (aggregated behavioral
 * metrics: engagement time, scroll depth, frustration signals) for the
 * weekly analytics ritual. Token lives in CLARITY_API_TOKEN (prod .env).
 * API: max 10 requests/day/project, last 1-3 days only.
 */
class ClarityReportCommand extends Command
{
    protected $signature = 'clarity:report {--days=1 : 1-3, last 24/48/72 hours}
                            {--dimension=URL : Browser|Device|Country/Region|OS|Source|Medium|Campaign|Channel|URL}';

    protected $description = 'Fetch behavioral metrics from the Microsoft Clarity Data Export API';

    public function handle(): int
    {
        $token = config('services.clarity.token');

        if (empty($token)) {
            $this->warn('CLARITY_API_TOKEN is not set — generate one in Clarity → Settings → Data Export.');

            return self::FAILURE;
        }

        $days = min(3, max(1, (int) $this->option('days')));
        $dimension = $this->option('dimension');

        try {
            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => "Bearer {$token}"])
                ->get('https://www.clarity.ms/export-data/api/v1/project-live-insights', [
                    'numOfDays' => $days,
                    'dimension1' => $dimension,
                ]);
        } catch (\Throwable $e) {
            Log::error('Clarity API request failed', ['error' => $e->getMessage()]);
            $this->error("Request failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if ($response->status() === 429) {
            $this->warn('Clarity daily API quota exhausted (10 requests/day).');

            return self::FAILURE;
        }

        if (! $response->ok()) {
            Log::error('Clarity API non-200', ['status' => $response->status(), 'body' => $response->body()]);
            $this->error("Clarity API returned HTTP {$response->status()}");

            return self::FAILURE;
        }

        $this->info("=== Clarity — last {$days} day(s), by {$dimension} ===");

        foreach ($response->json() ?? [] as $metric) {
            $name = $metric['metricName'] ?? 'Unknown';
            $rows = $metric['information'] ?? [];

            $this->newLine();
            $this->info($name.' ('.count($rows).' rows)');

            foreach (array_slice($rows, 0, 8) as $row) {
                $this->line('  '.json_encode($row, JSON_UNESCAPED_SLASHES));
            }
        }

        return self::SUCCESS;
    }
}
