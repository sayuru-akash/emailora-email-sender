<?php

namespace Tests\Feature\Seo;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeoRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_og_image_asset_exists_and_is_reasonably_sized(): void
    {
        $path = public_path('images/og/emailora.png');

        $this->assertFileExists($path);
        $this->assertLessThan(300 * 1024, filesize($path));

        [$width, $height] = getimagesize($path);
        $this->assertSame(1200, $width);
        $this->assertSame(630, $height);
    }

    public function test_public_pages_have_unique_titles_descriptions_and_canonicals(): void
    {
        config(['app.url' => 'https://emailora.codezela.com']);

        foreach ([
            route('home') => ['Email Campaign Operations Platform - Emailora', 'https://emailora.codezela.com/'],
            route('privacy') => ['Privacy Policy - Emailora', 'https://emailora.codezela.com/privacy'],
            route('terms') => ['Terms of Use - Emailora', 'https://emailora.codezela.com/terms'],
        ] as $url => [$title, $canonical]) {
            $this->get($url)
                ->assertOk()
                ->assertSee("<title>{$title}</title>", false)
                ->assertSee('<meta name="description"', false)
                ->assertSee('<link rel="canonical" href="'.$canonical.'"', false);
        }
    }

    public function test_public_pages_are_accessible_to_authenticated_and_inactive_users(): void
    {
        $inactive = User::factory()->create(['status' => 'inactive']);

        foreach ([route('home'), route('privacy'), route('terms')] as $url) {
            $this->actingAs($inactive)
                ->get($url)
                ->assertOk();
        }
    }
}
