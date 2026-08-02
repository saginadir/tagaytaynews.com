<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\PageView;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $views = fn () => PageView::query();

        $topPages = PageView::select('path', DB::raw('count(*) as views'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        $topReferrers = PageView::select('referrer', DB::raw('count(*) as views'))
            ->whereNotNull('referrer')
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('referrer')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        return Inertia::render('Dashboard', [
            'stats' => [
                'viewsToday' => $views()->whereDate('created_at', today())->count(),
                'views7d' => $views()->where('created_at', '>=', now()->subDays(7))->count(),
                'views30d' => $views()->where('created_at', '>=', now()->subDays(30))->count(),
                'uniqueVisitors7d' => PageView::where('created_at', '>=', now()->subDays(7))
                    ->whereNotNull('ip_hash')
                    ->distinct()
                    ->count('ip_hash'),
                'avgEngagedSeconds7d' => round(((float) Event::where('type', 'time')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->avg('value')) / 1000),
                'quizCompletions7d' => Event::where('type', 'feature')
                    ->where('target', 'quiz:complete')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'shares7d' => Event::whereIn('type', ['click', 'feature'])
                    ->where('target', 'like', 'share:%')
                    ->where('created_at', '>=', now()->subDays(7))
                    ->count(),
                'topPages' => $topPages,
                'topReferrers' => $topReferrers,
            ],
        ]);
    }
}
