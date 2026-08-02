<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\PageView;
use App\Models\Poll;
use App\Support\RidgeReport;
use App\Support\Seo;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $latest = Article::published()
            ->with(['category:id,name,slug', 'featuredImage'])
            ->take(7)
            ->get();

        $sections = Category::query()
            ->select('id', 'name', 'slug', 'description')
            ->whereHas('articles', fn ($query) => $query->published())
            ->with([
                'articles' => fn ($query) => $query->published()
                    ->with(['category:id,name,slug', 'featuredImage'])
                    ->take(3),
            ])
            ->orderBy('id')
            ->get();

        return Inertia::render('Home', [
            'hero' => $latest->first(),
            'latest' => $latest->slice(1)->values(),
            'sections' => $sections,
            'trending' => $this->trending(),
            'ridgeReport' => RidgeReport::get(),
            'poll' => $this->currentPoll($request),
            'seo' => Seo::make(
                title: 'Tagaytay News — News from the Ridge',
                description: 'Breaking news, weather and fog advisories, Taal Volcano updates, traffic, tourism, and food & drink for Tagaytay City and the ridge.',
                canonical: url('/'),
            ),
        ]);
    }

    /**
     * Most-read articles over the last 7 days, from first-party page views.
     * Hidden until at least 2 articles have traffic — no sad-looking section.
     */
    private function trending(): Collection
    {
        $paths = PageView::select('path', DB::raw('count(*) as views'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(20)
            ->pluck('path');

        $slugs = $paths->map(fn (string $path) => basename($path))->filter()->values()->all();

        if ($slugs === []) {
            return collect();
        }

        $trending = Article::published()
            ->whereIn('slug', $slugs)
            ->with(['category:id,name,slug', 'featuredImage'])
            ->get()
            ->sortBy(fn (Article $article) => array_search($article->slug, $slugs, true))
            ->take(5)
            ->values();

        return $trending->count() >= 2 ? $trending : collect();
    }

    private function currentPoll(Request $request): ?array
    {
        $poll = Poll::where('is_active', true)->with('options')->latest()->first();

        if ($poll === null) {
            return null;
        }

        $voterHash = Poll::voterHash((string) $request->ip(), $poll->id);
        $myVote = $poll->votes()->where('voter_hash', $voterHash)->value('poll_option_id');

        return [
            'id' => $poll->id,
            'question' => $poll->question,
            'totalVotes' => $poll->totalVotes(),
            'myOptionId' => $myVote,
            'options' => $poll->options->map(fn ($option) => [
                'id' => $option->id,
                'label' => $option->label,
                'votes' => $option->votes,
            ])->values(),
        ];
    }
}
