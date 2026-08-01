<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Support\Seo;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function show(Category $category)
    {
        return Inertia::render('Category', [
            'category' => $category,
            'articles' => $category->articles()
                ->published()
                ->with(['category:id,name,slug', 'featuredImage'])
                ->get(),
            'seo' => Seo::make(
                title: $category->name.' — Tagaytay News',
                description: $category->description ?: "Latest {$category->name} coverage from Tagaytay City and the ridge.",
                canonical: route('category.show', ['category' => $category->slug]),
            ),
        ]);
    }
}
