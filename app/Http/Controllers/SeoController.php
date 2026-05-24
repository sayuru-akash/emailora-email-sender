<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * @return array<int, array{path: string, lastmod: string, changefreq: string, priority: string}>
     */
    public static function sitemapPages(): array
    {
        return [
            ['path' => '/', 'lastmod' => '2026-05-24', 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['path' => '/privacy', 'lastmod' => '2026-05-24', 'changefreq' => 'yearly', 'priority' => '0.4'],
            ['path' => '/terms', 'lastmod' => '2026-05-24', 'changefreq' => 'yearly', 'priority' => '0.4'],
        ];
    }

    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /dashboard',
            'Disallow: /contacts',
            'Disallow: /campaigns',
            'Disallow: /imports',
            'Disallow: /lists',
            'Disallow: /reports',
            'Disallow: /segments',
            'Disallow: /settings',
            'Disallow: /tags',
            'Disallow: /templates',
            'Disallow: /users',
            'Disallow: /activity-logs',
            'Sitemap: '.$this->absoluteUrl('/sitemap.xml'),
        ];

        return response(implode("\n", $lines)."\n", 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400');
    }

    public function sitemap(): Response
    {
        $urls = collect(self::sitemapPages())
            ->map(fn (array $page) => $this->sitemapUrl($page))
            ->implode("\n");

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$urls}
</urlset>
XML;

        return response($xml."\n", 200)
            ->header('Content-Type', 'application/xml; charset=UTF-8')
            ->header('Cache-Control', 'public, max-age=3600, s-maxage=86400');
    }

    public static function publicPageSeo(string $path, string $title, string $description): array
    {
        $canonical = self::absoluteUrlFor($path);
        $image = self::absoluteUrlFor('/images/og/emailora.png');

        return [
            'title' => $title,
            'fullTitle' => "{$title} - Emailora",
            'description' => $description,
            'canonical' => $canonical,
            'image' => $image,
            'imageAlt' => 'Emailora campaign operations dashboard preview.',
            'imageWidth' => 1200,
            'imageHeight' => 630,
            'siteName' => 'Emailora',
            'robots' => 'index,follow',
            'locale' => 'en_US',
        ];
    }

    public static function absoluteUrlFor(string $path): string
    {
        $baseUrl = rtrim((string) config('app.url'), '/');

        if ($path === '/') {
            return $baseUrl.'/';
        }

        return $baseUrl.'/'.ltrim($path, '/');
    }

    /**
     * @param  array{path: string, lastmod: string, changefreq: string, priority: string}  $page
     */
    private function sitemapUrl(array $page): string
    {
        $location = htmlspecialchars($this->absoluteUrl($page['path']), ENT_XML1, 'UTF-8');

        return <<<XML
    <url>
        <loc>{$location}</loc>
        <lastmod>{$page['lastmod']}</lastmod>
        <changefreq>{$page['changefreq']}</changefreq>
        <priority>{$page['priority']}</priority>
    </url>
XML;
    }

    private function absoluteUrl(string $path): string
    {
        return self::absoluteUrlFor($path);
    }
}
