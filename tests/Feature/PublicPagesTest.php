<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_homepage_is_available_to_guests(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Welcome'));
    }

    public function test_public_privacy_page_is_available_to_guests(): void
    {
        $this->get(route('privacy'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legal/Privacy'));
    }

    public function test_public_terms_page_is_available_to_guests(): void
    {
        $this->get(route('terms'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Legal/Terms'));
    }

    public function test_dashboard_stays_authenticated(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login', absolute: false));
    }
}
