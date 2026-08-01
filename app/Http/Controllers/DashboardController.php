<?php

namespace App\Http\Controllers;

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
                'topPages' => $topPages,
                'topReferrers' => $topReferrers,
            ],
        ]);
    }
}
