<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Poll;
use App\Support\RidgeReport;
use App\Support\Seo;
use Illuminate\Http\Request;
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
            'ridgeReport' => RidgeReport::get(),
            'poll' => $this->currentPoll($request),
            'seo' => Seo::make(
                title: 'Tagaytay News — News from the Ridge',
                description: 'Breaking news, weather and fog advisories, Taal Volcano updates, traffic, tourism, and food & drink for Tagaytay City and the ridge.',
                canonical: url('/'),
            ),
        ]);
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
