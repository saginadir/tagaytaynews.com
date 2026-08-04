<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Media;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Image;
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
     * JPEG q82) using Laravel's image API (Intervention driver, GD/Imagick).
     * Returns [body, mime] — unchanged when already small or when no driver
     * is available (prod without GD keeps the original instead of failing).
     *
     * @return array{0: string, 1: string}
     */
    private function downscale(string $body, string $mime, string $url): array
    {
        try {
            $image = Image::fromBytes($body);

            if ($image->width() <= 1920 && $mime === 'image/jpeg' && strlen($body) < 1_500_000) {
                return [$body, $mime];
            }

            $resized = $image->scale(width: 1920)->optimize(format: 'jpg', quality: 82);

            return [(string) $resized, 'image/jpeg'];
        } catch (\Throwable $e) {
            Log::warning('Media import: image driver unavailable or decode failed, storing original', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [$body, $mime];
        }
    }
}
