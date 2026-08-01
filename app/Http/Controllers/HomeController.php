<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Support\Seo;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index()
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
            'seo' => Seo::make(
                title: 'Tagaytay News — News from the Ridge',
                description: 'Breaking news, weather and fog advisories, Taal Volcano updates, traffic, tourism, and food & drink for Tagaytay City and the ridge.',
                canonical: url('/'),
            ),
        ]);
    }
}
