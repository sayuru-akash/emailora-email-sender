<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class SitemapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://emailora.codezela.com']);
    }

    public function test_sitemap_xml_is_stateless_and_publicly_cacheable(): void
    {
        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=3600, public, s-maxage=86400')
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_sitemap_contains_only_public_absolute_canonical_urls(): void
    {
        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertSee('<loc>https://emailora.codezela.com/</loc>', false)
            ->assertSee('<loc>https://emailora.codezela.com/privacy</loc>', false)
            ->assertSee('<loc>https://emailora.codezela.com/terms</loc>', false)
            ->assertDontSee('/dashboard')
            ->assertDontSee('/campaigns')
            ->assertDontSee('/contacts');
    }

    public function test_sitemap_entries_have_valid_lastmod_changefreq_and_priority(): void
    {
        $content = $this->get(route('seo.sitemap'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<lastmod>\d{4}-\d{2}-\d{2}<\/lastmod>/', $content);
        $this->assertStringContainsString('<changefreq>weekly</changefreq>', $content);
        $this->assertStringContainsString('<changefreq>yearly</changefreq>', $content);
        $this->assertStringContainsString('<priority>1.0</priority>', $content);
        $this->assertStringContainsString('<priority>0.4</priority>', $content);
    }
}
