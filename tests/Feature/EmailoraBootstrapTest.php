<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailoraBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_single_owner_without_demo_data(): void
    {
        $this->seed();

        $this->assertDatabaseHas('users', [
            'email' => 'owner@example.com',
            'role' => 'owner',
            'status' => 'active',
        ]);
        $this->assertSame(1, User::count());
        $this->assertDatabaseCount('contacts', 0);
        $this->assertDatabaseCount('email_campaigns', 0);
    }

    public function test_public_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
