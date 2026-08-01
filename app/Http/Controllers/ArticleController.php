<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Support\Seo;
use Inertia\Inertia;

class ArticleController extends Controller
{
    public function show(Category $category, Article $article)
    {
        abort_if($article->category_id !== $category->id, 404);
        abort_if($article->status !== Article::STATUS_PUBLISHED, 404);
        abort_if($article->published_at === null || $article->published_at->isFuture(), 404);

        $article->load(['category', 'featuredImage', 'source']);

        $canonical = route('article.show', ['category' => $category->slug, 'article' => $article->slug]);
        $description = $article->seo_description ?: $article->excerpt;
        $image = $article->featuredImage ? url($article->featuredImage->url) : null;

        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $article->title,
            'image' => [$image ?? url('/og-default.png')],
            'datePublished' => $article->published_at->toAtomString(),
            'dateModified' => $article->updated_at->toAtomString(),
            'author' => [
                ['@type' => 'Person', 'name' => $article->author],
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => 'Tagaytay News',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => url('/android-chrome-512x512.png'),
                ],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical],
        ];

        if ($description) {
            $jsonLd['description'] = $description;
        }

        return Inertia::render('Article', [
            'article' => $article,
            'bodyHtml' => $article->body_html,
            'related' => Article::published()
                ->where('category_id', $article->category_id)
                ->whereKeyNot($article->id)
                ->with(['category:id,name,slug', 'featuredImage'])
                ->take(3)
                ->get(),
            'seo' => Seo::make(
                title: ($article->seo_title ?: $article->title).' — Tagaytay News',
                description: $description,
                canonical: $canonical,
                image: $image,
                ogType: 'article',
                jsonLd: $jsonLd,
            ),
        ]);
    }
}
