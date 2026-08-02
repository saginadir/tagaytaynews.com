<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Queries PostHog (HogQL) for the weekly analytics ritual: top pages,
 * frustration clicks, and session durations. Needs POSTHOG_PERSONAL_KEY
 * and POSTHOG_PROJECT_ID in .env (query API lives on the app host,
 * not the ingestion host).
 */
class PosthogReportCommand extends Command
{
    protected $signature = 'posthog:report {--days=7}';

    protected $description = 'Pull behavioral analytics from PostHog via HogQL';

    public function handle(): int
    {
        $key = config('services.posthog.personal_key');
        $projectId = config('services.posthog.project_id');

        if (empty($key) || empty($projectId)) {
            $this->warn('POSTHOG_PERSONAL_KEY / POSTHOG_PROJECT_ID not set — see MISSION.md.');

            return self::FAILURE;
        }

        $days = min(30, max(1, (int) $this->option('days')));

        $queries = [
            'Top pages (views)' => "SELECT properties.\$current_url AS url, count() AS views FROM events WHERE event = '\$pageview' AND timestamp > now() - INTERVAL {$days} DAY GROUP BY url ORDER BY views DESC LIMIT 10",
            'Rage clicks by page' => "SELECT properties.\$current_url AS url, count() AS clicks FROM events WHERE event = '\$rageclick' AND timestamp > now() - INTERVAL {$days} DAY GROUP BY url ORDER BY clicks DESC LIMIT 5",
            'Session duration (avg/median seconds)' => "SELECT round(avg(\$session_duration)), round(quantile(0.5)(\$session_duration)) FROM sessions WHERE \$start_timestamp > now() - INTERVAL {$days} DAY",
        ];

        foreach ($queries as $label => $hogql) {
            $this->newLine();
            $this->info($label.':');

            $rows = $this->query($projectId, $key, $hogql);

            if ($rows === null) {
                return self::FAILURE;
            }

            foreach ($rows as $row) {
                $this->line('  '.implode('  |  ', array_map(fn ($v) => (string) $v, $row)));
            }

            if ($rows === []) {
                $this->line('  (no data)');
            }
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array<int, mixed>>|null
     */
    private function query(string $projectId, string $key, string $hogql): ?array
    {
        $host = rtrim((string) config('services.posthog.api_host', 'https://eu.posthog.com'), '/');

        try {
            $response = Http::timeout(30)
                ->withToken($key)
                ->post("{$host}/api/projects/{$projectId}/query/", [
                    'query' => ['kind' => 'HogQLQuery', 'query' => $hogql],
                ]);
        } catch (\Throwable $e) {
            Log::error('PostHog query failed', ['error' => $e->getMessage()]);
            $this->error("Request failed: {$e->getMessage()}");

            return null;
        }

        if (! $response->ok()) {
            Log::error('PostHog query non-200', ['status' => $response->status(), 'body' => $response->body()]);
            $this->error("PostHog API returned HTTP {$response->status()}");

            return null;
        }

        return $response->json('results') ?? [];
    }
}
