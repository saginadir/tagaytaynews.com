<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Client-side event beacon (see resources/js/tracker.ts). Privacy rules match
 * the page-view tracker: no cookies, no raw IPs, bots and admin paths skipped.
 */
class EventController extends Controller
{
    private const BOT_MARKERS = [
        'bot', 'crawl', 'spider', 'slurp', 'curl', 'wget', 'python', 'scrapy', 'headless',
    ];

    public function store(Request $request)
    {
        $agent = mb_strtolower((string) $request->userAgent());
        foreach (self::BOT_MARKERS as $marker) {
            if (str_contains($agent, $marker)) {
                return response()->noContent();
            }
        }

        $validated = $request->validate([
            'type' => 'required|string|in:'.implode(',', Event::TYPES),
            'path' => 'required|string|max:255',
            'target' => 'nullable|string|max:255',
            'value' => 'nullable|integer|min:0|max:86400000',
        ]);

        $path = '/'.ltrim((string) parse_url($validated['path'], PHP_URL_PATH), '/');

        // Never record back-office interactions.
        $adminPath = trim((string) config('admin.path'), '/');
        if ($adminPath !== '' && str_starts_with(trim($path, '/'), $adminPath)) {
            return response()->noContent();
        }

        try {
            Event::create([
                'session' => hash('sha256', $request->ip().'|'.now()->toDateString().'|'.config('app.key')),
                'type' => $validated['type'],
                'path' => $path,
                'target' => $validated['target'] ?? null,
                'value' => $validated['value'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('Event tracking failed', ['error' => $e->getMessage()]);
        }

        return response()->noContent();
    }
}
