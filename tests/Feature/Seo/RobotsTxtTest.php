<?php

namespace Tests\Feature\Seo;

use Tests\TestCase;

class RobotsTxtTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://emailora.codezela.com']);
    }

    public function test_robots_txt_is_stateless_plain_text_with_public_cache_headers(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertHeader('Cache-Control', 'max-age=3600, public, s-maxage=86400')
            ->assertHeaderMissing('Set-Cookie');
    }

    public function test_robots_txt_disallows_workspace_routes_and_references_sitemap(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertSeeText('User-agent: *')
            ->assertSeeText('Disallow: /dashboard')
            ->assertSeeText('Disallow: /campaigns')
            ->assertSeeText('Disallow: /contacts')
            ->assertSeeText('Disallow: /settings')
            ->assertSeeText('Sitemap: https://emailora.codezela.com/sitemap.xml')
            ->assertDontSeeText('Disallow: /privacy')
            ->assertDontSeeText('Disallow: /terms');
    }
}
