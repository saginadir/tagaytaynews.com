<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Event;
use App\Models\PageView;
use App\Models\Poll;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Weekly traffic + content digest — run at the start of an analytics
 * session to decide what to double down on (see MISSION.md rituals).
 */
class AnalyticsReportCommand extends Command
{
    protected $signature = 'analytics:report {--days=7 : Window to report on}';

    protected $description = 'Print a traffic and content digest for the weekly analytics ritual';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $since = now()->subDays($days);
        $priorSince = now()->subDays($days * 2);

        $views = PageView::where('created_at', '>=', $since)->count();
        $priorViews = PageView::whereBetween('created_at', [$priorSince, $since])->count();
        $uniques = PageView::where('created_at', '>=', $since)->whereNotNull('ip_hash')->distinct()->count('ip_hash');
        $delta = $priorViews > 0 ? round((($views - $priorViews) / $priorViews) * 100) : null;

        $this->info("=== Tagaytay News — last {$days} days ===");
        $this->line(sprintf(
            'Page views: %d (%s vs prior period) · Unique visitors: %d',
            $views,
            $delta === null ? 'no baseline' : ($delta >= 0 ? "+{$delta}%" : "{$delta}%"),
            $uniques,
        ));

        $this->newLine();
        $this->info('Top pages:');
        $topPages = PageView::select('path', DB::raw('count(*) as views'))
            ->where('created_at', '>=', $since)
            ->groupBy('path')->orderByDesc('views')->limit(10)->get();

        if ($topPages->isEmpty()) {
            $this->line('  (no traffic yet)');
        }
        foreach ($topPages as $row) {
            $this->line(sprintf('  %5d  %s', $row->views, $row->path));
        }

        $this->newLine();
        $this->info('Top referrers:');
        $referrers = PageView::select('referrer', DB::raw('count(*) as views'))
            ->whereNotNull('referrer')
            ->where('created_at', '>=', $since)
            ->groupBy('referrer')->orderByDesc('views')->limit(10)->get();

        if ($referrers->isEmpty()) {
            $this->line('  (no external referrers yet — distribution is the bottleneck, not content)');
        }
        foreach ($referrers as $row) {
            $this->line(sprintf('  %5d  %s', $row->views, parse_url((string) $row->referrer, PHP_URL_HOST) ?: $row->referrer));
        }

        $this->newLine();
        $drafts = Article::where('status', 'draft')->count();
        $published = Article::where('status', 'published')->count();
        $this->info("Content: {$published} published · {$drafts} drafts awaiting editorial");

        $this->newLine();
        $this->info('Poll pulse:');
        foreach (Poll::with('options')->latest()->take(1)->get() as $poll) {
            $this->line("  {$poll->question} ({$poll->totalVotes()} votes)");
            foreach ($poll->options as $option) {
                $this->line("    {$option->votes} — {$option->label}");
            }
        }

        $this->engagement($since);

        return self::SUCCESS;
    }

    private function engagement(CarbonInterface $since): void
    {
        $this->newLine();
        $this->info('Engagement:');

        $sessions = Event::where('created_at', '>=', $since)->distinct()->count('session');
        $avgSeconds = round(((float) Event::where('type', 'time')->where('created_at', '>=', $since)->avg('value')) / 1000);
        $avgScroll = round((float) Event::where('type', 'time')->where('created_at', '>=', $since)
            ->get()->avg(fn ($e) => (float) $e->target));

        $this->line("  Sessions: {$sessions} · Avg engaged time: {$avgSeconds}s · Avg max scroll: {$avgScroll}%");

        $byPage = Event::select('path', DB::raw('avg(value) as avg_ms'), DB::raw('count(*) as reads'))
            ->where('type', 'time')
            ->where('created_at', '>=', $since)
            ->groupBy('path')->orderByDesc('avg_ms')->limit(5)->get();

        foreach ($byPage as $row) {
            $this->line(sprintf('  %6ss  %s (%d reads)', round($row->avg_ms / 1000), $row->path, $row->reads));
        }

        $features = Event::select('target', DB::raw('count(*) as hits'))
            ->where('type', 'feature')
            ->where('created_at', '>=', $since)
            ->groupBy('target')->orderByDesc('hits')->limit(10)->get();

        if ($features->isNotEmpty()) {
            $this->line('  Feature usage:');
            foreach ($features as $row) {
                $this->line(sprintf('  %6d  %s', $row->hits, $row->target));
            }
        }

        $outbound = Event::select('target', DB::raw('count(*) as hits'))
            ->where('type', 'outbound')
            ->where('created_at', '>=', $since)
            ->groupBy('target')->orderByDesc('hits')->limit(5)->get();

        if ($outbound->isNotEmpty()) {
            $this->line('  Outbound clicks:');
            foreach ($outbound as $row) {
                $host = parse_url((string) $row->target, PHP_URL_HOST) ?: $row->target;
                $this->line(sprintf('  %6d  %s', $row->hits, $host));
            }
        }
    }
}
