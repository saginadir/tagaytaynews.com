<?php

namespace App\Support;

class Seo
{
    /**
     * Build the `seo` prop consumed both by the Blade root template (initial
     * HTML, so crawlers always see it) and the Vue <Head> (client-side
     * navigations).
     *
     * @return array{title: string, description: ?string, canonical: ?string, ogImage: string, ogType: string, jsonLd: ?string}
     */
    public static function make(
        string $title,
        ?string $description = null,
        ?string $canonical = null,
        ?string $image = null,
        string $ogType = 'website',
        ?array $jsonLd = null,
    ): array {
        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'ogImage' => $image ?? url('/og-default.png'),
            'ogType' => $ogType,
            'jsonLd' => $jsonLd === null
                ? null
                : json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }
}
