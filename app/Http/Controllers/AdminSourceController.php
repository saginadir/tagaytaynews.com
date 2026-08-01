<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class AdminSourceController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Sources', [
            'sources' => Source::orderBy('name')->get(),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);

        try {
            Source::create($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to create source', ['error' => $e->getMessage(), 'name' => $validated['name'] ?? null]);
            throw $e;
        }

        return redirect()->route('admin.sources.index');
    }

    public function update(Request $request, Source $source)
    {
        $validated = $this->validated($request);

        try {
            $source->update($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to update source', ['error' => $e->getMessage(), 'source_id' => $source->id]);
            throw $e;
        }

        return redirect()->route('admin.sources.index');
    }

    public function destroy(Source $source)
    {
        try {
            $source->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete source', ['error' => $e->getMessage(), 'source_id' => $source->id]);
            throw $e;
        }

        return redirect()->route('admin.sources.index');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|max:255',
            'tier' => 'required|integer|between:1,3',
            'notes' => 'nullable|string|max:1000',
        ]);
    }
}
