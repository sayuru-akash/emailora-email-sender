<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivatePageSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_pages_are_not_indexable_in_html_or_response_headers(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive"', false)
            ->assertDontSee('<link rel="canonical"', false);
    }

    public function test_public_pages_do_not_receive_private_crawler_directives(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertHeaderMissing('X-Robots-Tag');
    }

    public function test_auxiliary_public_documents_are_not_search_results(): void
    {
        $this->get(route('seo.robots'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');

        $this->get(route('seo.sitemap'))
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive');
    }

    public function test_unknown_html_routes_render_a_branded_non_indexable_not_found_page(): void
    {
        $this->get('/this-route-does-not-exist')
            ->assertNotFound()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive')
            ->assertSee('Page not found - Emailora')
            ->assertSee('Page not found')
            ->assertSee('<meta name="robots" content="noindex, nofollow, noarchive"', false);
    }
}
