<?php

namespace App\Http\Middleware;

use App\Models\PageView;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Privacy-friendly first-party analytics: counts page views with no cookies
 * and no raw IPs (a daily-rotating salted hash estimates unique visitors).
 */
class TrackPageView
{
    private const BOT_MARKERS = [
        'bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'python', 'scrapy',
        'headless', 'monitor', 'uptime', 'pingdom', 'lighthouse', 'facebookexternalhit',
        'whatsapp', 'telegram', 'discord', 'preview',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->shouldTrack($request, $response)) {
            try {
                PageView::create([
                    'path' => '/'.ltrim($request->path(), '/'),
                    'referrer' => $this->cleanReferrer($request),
                    'ip_hash' => $request->ip()
                        ? hash('sha256', $request->ip().'|'.now()->toDateString().'|'.config('app.key'))
                        : null,
                ]);
            } catch (\Throwable $e) {
                Log::error('Page view tracking failed', ['path' => $request->path(), 'error' => $e->getMessage()]);
            }
        }

        return $response;
    }

    private function shouldTrack(Request $request, Response $response): bool
    {
        if ($request->method() !== 'GET' || $response->getStatusCode() !== 200) {
            return false;
        }

        // Inertia SPA navigations return JSON to XHR — they are real page views.
        // But non-Inertia JSON/asset responses are not.
        if (! $request->header('X-Inertia') && ! str_contains((string) $response->headers->get('Content-Type'), 'text/html')) {
            return false;
        }

        $path = trim($request->path(), '/');
        $skipExact = ['', 'login', 'register', 'dashboard', 'up', 'robots.txt', 'sitemap.xml', 'feed.xml', 'llms.txt'];

        if ($path !== '' && in_array($path, $skipExact, true)) {
            return false;
        }

        $adminPath = trim((string) config('admin.path'), '/');
        if ($adminPath !== '' && str_starts_with($path, $adminPath)) {
            return false;
        }

        $agent = mb_strtolower((string) $request->userAgent());
        foreach (self::BOT_MARKERS as $marker) {
            if (str_contains($agent, $marker)) {
                return false;
            }
        }

        return true;
    }

    private function cleanReferrer(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        if ($referrer === null || $referrer === '') {
            return null;
        }

        // Same-site referrers add noise — only external referrers matter.
        $host = parse_url($referrer, PHP_URL_HOST);
        if ($host !== null && strcasecmp((string) $host, (string) $request->getHost()) === 0) {
            return null;
        }

        return mb_substr($referrer, 0, 255);
    }
}
