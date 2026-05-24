<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'light') === 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline style keeps the first paint aligned with the selected interface. --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: #09090b;
            }
        </style>

        <meta name="application-name" content="Emailora">
        <meta name="apple-mobile-web-app-title" content="Emailora">
        <meta name="theme-color" content="#4f46e5">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png" sizes="180x180">
        <link rel="manifest" href="/site.webmanifest">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>{{ config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
        @php
            $component = $page['component'] ?? '';
            $seo = $page['props']['seo'] ?? null;
        @endphp
        @if ($seo)
            <title>{{ $seo['fullTitle'] }}</title>
            <meta name="description" content="{{ $seo['description'] }}">
            <meta name="robots" content="{{ $seo['robots'] }}">
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            <meta property="og:type" content="{{ $component === 'Welcome' ? 'website' : 'article' }}">
            <meta property="og:title" content="{{ $seo['fullTitle'] }}">
            <meta property="og:description" content="{{ $seo['description'] }}">
            <meta property="og:url" content="{{ $seo['canonical'] }}">
            <meta property="og:image" content="{{ $seo['image'] }}">
            <meta property="og:image:alt" content="{{ $seo['imageAlt'] }}">
            <meta property="og:image:width" content="{{ $seo['imageWidth'] }}">
            <meta property="og:image:height" content="{{ $seo['imageHeight'] }}">
            <meta property="og:site_name" content="{{ $seo['siteName'] }}">
            <meta property="og:locale" content="{{ $seo['locale'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seo['fullTitle'] }}">
            <meta name="twitter:description" content="{{ $seo['description'] }}">
            <meta name="twitter:image" content="{{ $seo['image'] }}">
            <meta name="twitter:image:alt" content="{{ $seo['imageAlt'] }}">
        @elseif (\Illuminate\Support\Str::startsWith($component, 'auth/'))
            <meta name="robots" content="noindex,follow">
        @endif
        @if ($component === 'Welcome' && $seo)
            @php
                $structuredData = [
                    '@context' => 'https://schema.org',
                    '@graph' => [
                        [
                            '@type' => 'Organization',
                            '@id' => $seo['canonical'].'#organization',
                            'name' => 'Codezela Technologies',
                            'url' => $seo['canonical'],
                            'brand' => [
                                '@type' => 'Brand',
                                'name' => 'Emailora',
                            ],
                            'logo' => $seo['image'],
                        ],
                        [
                            '@type' => 'WebApplication',
                            '@id' => $seo['canonical'].'#application',
                            'name' => 'Emailora',
                            'applicationCategory' => 'BusinessApplication',
                            'operatingSystem' => 'Web',
                            'url' => $seo['canonical'],
                            'image' => $seo['image'],
                            'description' => $seo['description'],
                            'publisher' => [
                                '@id' => $seo['canonical'].'#organization',
                            ],
                        ],
                    ],
                ];
            @endphp
            <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
        @endif
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
