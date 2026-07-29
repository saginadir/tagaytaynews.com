<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AdminMediaController extends Controller
{
    public function index()
    {
        return Inertia::render('admin/Media', [
            'media' => Media::latest()->get(),
            'adminPath' => config('admin.path'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,svg,mp4,webm,mov',
        ]);

        $file = $request->file('file');
        $path = $file->store('media', 'public');

        try {
            Media::create([
                'filename' => $file->getClientOriginalName(),
                'disk_path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to create media record', ['error' => $e->getMessage()]);
            Storage::disk('public')->delete($path);
            throw $e;
        }

        return redirect()->route('admin.media.index');
    }

    public function update(Request $request, Media $medium)
    {
        $validated = $request->validate([
            'alt' => 'nullable|string|max:255',
        ]);

        try {
            $medium->update($validated);
        } catch (\Throwable $e) {
            Log::error('Failed to update media', ['error' => $e->getMessage(), 'media_id' => $medium->id]);
            throw $e;
        }

        return redirect()->route('admin.media.index');
    }

    public function destroy(Media $medium)
    {
        try {
            Storage::disk('public')->delete($medium->disk_path);
            $medium->delete();
        } catch (\Throwable $e) {
            Log::error('Failed to delete media', ['error' => $e->getMessage(), 'media_id' => $medium->id]);
            throw $e;
        }

        return redirect()->route('admin.media.index');
    }
}
