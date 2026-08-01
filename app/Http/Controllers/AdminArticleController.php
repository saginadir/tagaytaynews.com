<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use App\Models\Media;
use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AdminArticleController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/articles/Index', [
            'articles' => Article::with('category:id,name')->latest()->get(),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/articles/Form', [
            'article' => null,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'media' => Media::latest()->get(),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            Article::create($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to create article', ['error' => $e->getMessage(), 'title' => $validated['title'] ?? null]);
            throw $e;
        }

        return redirect()->route('admin.articles.index');
    }

    public function edit(Article $article)
    {
        return Inertia::render('admin/articles/Form', [
            'article' => $article,
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'sources' => Source::orderBy('name')->get(['id', 'name']),
            'media' => Media::latest()->get(),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function update(Request $request, Article $article)
    {
        $validated = $this->validated($request, $article);

        try {
            $article->update($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to update article', ['error' => $e->getMessage(), 'article_id' => $article->id]);
            throw $e;
        }

        return redirect()->route('admin.articles.index');
    }

    public function destroy(Article $article)
    {
        try {
            $article->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete article', ['error' => $e->getMessage(), 'article_id' => $article->id]);
            throw $e;
        }

        return redirect()->route('admin.articles.index');
    }

    private function validated(Request $request, ?Article $article = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'alpha_dash', 'max:255', Rule::unique('articles', 'slug')->ignore($article)],
            'excerpt' => 'nullable|string',
            'body' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'source_id' => 'nullable|exists:sources,id',
            'source_url' => 'nullable|url|max:255',
            'featured_image_id' => 'nullable|exists:media,id',
            'author' => 'nullable|string|max:255',
            'status' => 'required|in:draft,published',
            'published_at' => 'nullable|date|required_if:status,published',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
        ]);

        if (empty($validated['author'])) {
            $validated['author'] = 'Tagaytay News Staff';
        }

        return $validated;
    }
}
