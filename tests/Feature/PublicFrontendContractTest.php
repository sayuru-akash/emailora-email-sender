<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicFrontendContractTest extends TestCase
{
    public function test_public_layout_contains_crawlable_navigation_skip_link_and_theme_toggle(): void
    {
        $layout = file_get_contents(resource_path('js/layouts/public/PublicLayout.vue'));

        $this->assertStringContainsString('Skip to content', $layout);
        $this->assertStringContainsString('aria-label="Primary"', $layout);
        $this->assertStringContainsString('href="/privacy"', $layout);
        $this->assertStringContainsString('href="/terms"', $layout);
        $this->assertStringContainsString('<ThemeToggle />', $layout);
    }

    public function test_public_pages_do_not_use_authenticated_app_shell(): void
    {
        $app = file_get_contents(resource_path('js/app.ts'));

        $this->assertStringContainsString("name === 'Welcome' || name.startsWith('Legal/')", $app);
    }

    public function test_public_pages_do_not_contain_placeholder_copy(): void
    {
        foreach ([
            resource_path('js/pages/Welcome.vue'),
            resource_path('js/pages/Legal/Privacy.vue'),
            resource_path('js/pages/Legal/Terms.vue'),
        ] as $path) {
            $content = strtolower(file_get_contents($path));

            $this->assertStringNotContainsString('lorem ipsum', $content);
            $this->assertStringNotContainsString('placeholder', $content);
            $this->assertStringNotContainsString('coming soon', $content);
            $this->assertStringNotContainsString('todo', $content);
        }
    }

    public function test_public_homepage_hides_decorative_icons_from_assistive_technology(): void
    {
        $homepage = file_get_contents(resource_path('js/pages/Welcome.vue'));

        $this->assertStringContainsString('aria-hidden="true"', $homepage);
        $this->assertStringContainsString('focusable="false"', $homepage);
    }
}
