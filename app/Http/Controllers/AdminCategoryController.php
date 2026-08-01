<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminCategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Categories', [
            'categories' => Category::withCount('articles')->orderBy('name')->get(),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            Category::create($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to create category', ['error' => $e->getMessage(), 'name' => $validated['name'] ?? null]);
            throw $e;
        }

        return redirect()->route('admin.categories.index');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        try {
            $category->update($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to update category', ['error' => $e->getMessage(), 'category_id' => $category->id]);
            throw $e;
        }

        return redirect()->route('admin.categories.index');
    }

    public function destroy(Category $category)
    {
        if ($category->articles()->exists()) {
            return back()->withErrors(['message' => 'Cannot delete a category that still has articles.']);
        }

        try {
            $category->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete category', ['error' => $e->getMessage(), 'category_id' => $category->id]);
            throw $e;
        }

        return redirect()->route('admin.categories.index');
    }
}
