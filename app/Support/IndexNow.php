<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * IndexNow: instantly notify Bing/Yandex about new or updated URLs.
 * Free, no account — the key is a public token served from /{key}.txt.
 */
class IndexNow
{
    public static function key(): string
    {
        $key = Setting::get('indexnow_key');

        if ($key === null) {
            $key = Str::lower(Str::random(32));
            Setting::set('indexnow_key', $key);
        }

        return $key;
    }

    /**
     * @param  string[]  $urls  Absolute URLs to submit (max 10,000 per call).
     */
    public static function ping(array $urls): bool
    {
        if ($urls === []) {
            return true;
        }

        $key = static::key();

        try {
            $response = Http::timeout(10)->post('https://api.indexnow.org/indexnow', [
                'host' => parse_url(config('app.url'), PHP_URL_HOST),
                'key' => $key,
                'keyLocation' => config('app.url').'/'.$key.'.txt',
                'urlList' => array_values($urls),
            ]);
        } catch (\Throwable $e) {
            Log::error('IndexNow ping failed', ['error' => $e->getMessage(), 'urls' => count($urls)]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('IndexNow ping non-2xx', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        }

        Log::info('IndexNow ping sent', ['urls' => count($urls)]);

        return true;
    }
}
