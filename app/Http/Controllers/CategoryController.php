<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Support\Seo;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        $articles = $category->articles()
            ->published()
            ->with(['category:id,name,slug', 'featuredImage'])
            ->get();

        return Inertia::render('Category', [
            'category' => $category,
            'articles' => $articles,
            // Empty sections funnel readers to live content instead of a dead end.
            'fallback' => $articles->isEmpty()
                ? Article::published()
                    ->with(['category:id,name,slug', 'featuredImage'])
                    ->take(6)
                    ->get()
                : collect(),
            'seo' => Seo::make(
                title: $category->name.' — Tagaytay News',
                description: $category->description ?: "Latest {$category->name} coverage from Tagaytay City and the ridge.",
                canonical: route('category.show', ['category' => $category->slug]),
            ),
        ]);
    }
}
