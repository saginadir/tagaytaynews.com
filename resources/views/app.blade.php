<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <title data-inertia>{{ $page['props']['seo']['title'] ?? config('app.name', 'Laravel') }}</title>

        {{-- SEO block rendered server-side from the page's `seo` prop, so crawlers
             see correct meta without relying on SSR or client-side rendering. --}}
        @if (! empty($page['props']['seo']))
            @php($seo = $page['props']['seo'])
            @if (! empty($seo['description']))
                <meta name="description" content="{{ $seo['description'] }}">
            @endif
            @if (! empty($seo['canonical']))
                <link rel="canonical" href="{{ $seo['canonical'] }}">
                <meta property="og:url" content="{{ $seo['canonical'] }}">
            @endif
            <meta property="og:title" content="{{ $seo['title'] }}">
            @if (! empty($seo['description']))
                <meta property="og:description" content="{{ $seo['description'] }}">
                <meta name="twitter:description" content="{{ $seo['description'] }}">
            @endif
            <meta property="og:type" content="{{ $seo['ogType'] }}">
            <meta property="og:image" content="{{ $seo['ogImage'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seo['title'] }}">
            <meta name="twitter:image" content="{{ $seo['ogImage'] }}">
            @if (! empty($seo['jsonLd']))
                <script type="application/ld+json">{!! $seo['jsonLd'] !!}</script>
            @endif
        @endif

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="#0B3D2E">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
