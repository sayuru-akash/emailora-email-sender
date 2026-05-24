<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveUserWorkspaceBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_users_are_logged_out_from_workspace_routes(): void
    {
        $inactive = User::factory()->create(['status' => 'inactive']);

        $this->actingAs($inactive)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_users_cannot_access_profile_security_or_appearance_settings(): void
    {
        $inactive = User::factory()->create(['status' => 'inactive', 'email_verified_at' => now()]);

        foreach ([route('profile.edit'), route('profile.show'), route('security.edit'), route('appearance.edit')] as $url) {
            $this->actingAs($inactive)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get($url)
                ->assertRedirect(route('login'));
        }
    }
}
