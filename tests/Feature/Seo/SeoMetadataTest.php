<?php

namespace Tests\Feature\Seo;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SeoMetadataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://emailora.codezela.com']);
    }

    public function test_homepage_initial_html_has_complete_search_and_social_metadata(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('<title data-inertia>Email Campaign Operations Platform - Emailora</title>', false)
            ->assertSee('<meta name="description" content="Emailora is a clean campaign operations console for contact imports, audience targeting, templates, queued email sends, reporting, and audit logs."', false)
            ->assertSee('<meta name="robots" content="index,follow"', false)
            ->assertSee('<link rel="canonical" href="https://emailora.codezela.com/"', false)
            ->assertSee('<meta property="og:type" content="website"', false)
            ->assertSee('<meta property="og:title" content="Email Campaign Operations Platform - Emailora"', false)
            ->assertSee('<meta property="og:url" content="https://emailora.codezela.com/"', false)
            ->assertSee('<meta property="og:image" content="https://emailora.codezela.com/images/og/emailora.png"', false)
            ->assertSee('<meta property="og:image:alt" content="Emailora campaign operations dashboard preview."', false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image"', false)
            ->assertSee('<meta name="twitter:image:alt" content="Emailora campaign operations dashboard preview."', false);
    }

    public function test_legal_pages_have_unique_absolute_metadata(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertSee('<title data-inertia>Privacy Policy - Emailora</title>', false)
            ->assertSee('<link rel="canonical" href="https://emailora.codezela.com/privacy"', false)
            ->assertSee('<meta property="og:type" content="article"', false);

        $this->get(route('terms'))
            ->assertOk()
            ->assertSee('<title data-inertia>Terms of Use - Emailora</title>', false)
            ->assertSee('<link rel="canonical" href="https://emailora.codezela.com/terms"', false)
            ->assertSee('<meta property="og:type" content="article"', false);
    }

    public function test_homepage_exposes_valid_json_ld_without_unverified_pricing(): void
    {
        $response = $this->get(route('home'))->assertOk();

        $response
            ->assertSee('application/ld+json', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"@type":"WebApplication"', false)
            ->assertSee('"name":"Emailora"', false)
            ->assertDontSee('"@type":"Offer"', false)
            ->assertDontSee('innerHTML=', false)
            ->assertDontSee('textContent=', false);
    }

    public function test_auth_pages_are_noindex_follow(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,follow"', false);
    }

    public function test_production_app_url_cannot_be_localhost(): void
    {
        config([
            'app.env' => 'production',
            'app.url' => 'http://localhost:8000',
        ]);

        $provider = new AppServiceProvider($this->app);
        $method = new \ReflectionMethod($provider, 'ensureProductionAppUrlIsPublic');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('APP_URL must be a public production URL');

        $method->invoke($provider);
    }
}
