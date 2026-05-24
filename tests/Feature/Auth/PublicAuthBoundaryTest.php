<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicAuthBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_active_users_and_inactive_users_can_access_public_pages(): void
    {
        foreach ([route('home'), route('privacy'), route('terms')] as $url) {
            $this->get($url)->assertOk();
        }

        $activeUser = User::factory()->create();

        foreach ([route('home'), route('privacy'), route('terms')] as $url) {
            $this->actingAs($activeUser)->get($url)->assertOk();
        }

        $inactiveUser = User::factory()->create(['status' => 'inactive']);

        foreach ([route('home'), route('privacy'), route('terms')] as $url) {
            $this->actingAs($inactiveUser)->get($url)->assertOk();
        }
    }

    public function test_guests_cannot_access_authenticated_workspace_routes(): void
    {
        foreach ([
            route('dashboard'),
            route('contacts.index'),
            route('campaigns.index'),
            route('imports.index'),
            route('settings.index'),
        ] as $url) {
            $this->get($url)->assertRedirect(route('login', absolute: false));
        }
    }

    public function test_staff_users_cannot_access_owner_admin_routes(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }
}
