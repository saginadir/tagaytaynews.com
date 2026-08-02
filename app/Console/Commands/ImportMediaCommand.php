<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Downloads a remote image into the media library, so properly licensed
 * photography (e.g. Wikimedia Commons) can be attached to articles.
 */
class ImportMediaCommand extends Command
{
    protected $signature = 'media:import {url : Remote image URL}
                            {--alt= : Alt text}
                            {--credit= : Attribution line (author, license, source)}
                            {--article= : Attach as featured image of the article with this slug}';

    protected $description = 'Import a remote image into the media library';

    public function handle(): int
    {
        $url = $this->argument('url');

        try {
            $response = Http::timeout(30)
                ->withHeaders(['User-Agent' => config('newsroom.user_agent')])
                ->get($url);
        } catch (\Throwable $e) {
            Log::error('Media import fetch failed', ['url' => $url, 'error' => $e->getMessage()]);
            $this->error("Fetch failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        if (! $response->ok()) {
            Log::error('Media import non-200', ['url' => $url, 'status' => $response->status()]);
            $this->error("HTTP {$response->status()}");

            return self::FAILURE;
        }

        $mime = $response->header('Content-Type');
        $mime = $mime !== null ? trim(explode(';', $mime)[0]) : '';

        $extensions = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];

        if (! isset($extensions[$mime])) {
            Log::error('Media import rejected content type', ['url' => $url, 'mime' => $mime]);
            $this->error("Unsupported content type: {$mime}");

            return self::FAILURE;
        }

        [$body, $mime] = $this->downscale($response->body(), $mime, $url);

        $filename = uniqid('media_').'.'.$extensions[$mime];
        $diskPath = 'media/'.$filename;

        if (! \Storage::disk('public')->put($diskPath, $body)) {
            Log::error('Media import storage write failed', ['url' => $url, 'path' => $diskPath]);
            $this->error('Could not write to storage.');

            return self::FAILURE;
        }

        $media = Media::create([
            'filename' => $filename,
            'disk_path' => $diskPath,
            'mime_type' => $mime,
            'size' => strlen($body),
            'alt' => $this->option('alt'),
            'credit' => $this->option('credit'),
        ]);

        $this->info("Imported media #{$media->id} ({$diskPath})");

        if ($slug = $this->option('article')) {
            $article = Article::where('slug', $slug)->first();

            if ($article === null) {
                $this->warn("No article with slug '{$slug}' — image imported but not attached.");

                return self::SUCCESS;
            }

            $article->update(['featured_image_id' => $media->id]);
            $this->info("Attached to article '{$slug}'.");
        }

        return self::SUCCESS;
    }

    /**
     * Downscale oversized images to web-friendly dimensions (max 1920px wide,
     * JPEG q82). Returns [body, mime] — unchanged when already small.
     *
     * @return array{0: string, 1: string}
     */
    private function downscale(string $body, string $mime, string $url): array
    {
        if (! function_exists('imagecreatefromstring')) {
            Log::warning('Media import: GD extension unavailable, storing original', ['url' => $url]);

            return [$body, $mime];
        }

        $image = @imagecreatefromstring($body);

        if ($image === false) {
            Log::warning('Media import could not decode image for resize', ['url' => $url]);

            return [$body, $mime];
        }

        $width = imagesx($image);

        if ($width <= 1920 && $mime === 'image/jpeg' && strlen($body) < 1_500_000) {
            return [$body, $mime];
        }

        $height = imagesy($image);
        $newWidth = min($width, 1920);
        $newHeight = (int) round($height * ($newWidth / $width));
        $scaled = imagescale($image, $newWidth, $newHeight);

        if ($scaled === false) {
            return [$body, $mime];
        }

        ob_start();
        imagejpeg($scaled, null, 82);
        $resized = (string) ob_get_clean();

        return [$resized, 'image/jpeg'];
    }
}
