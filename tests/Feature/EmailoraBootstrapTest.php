<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
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

    public function test_production_seeder_rejects_default_owner_password(): void
    {
        $originalEnvironment = app()->environment();
        app()->detectEnvironment(fn () => 'production');
        putenv('OWNER_PASSWORD=password');
        $_ENV['OWNER_PASSWORD'] = 'password';
        $_SERVER['OWNER_PASSWORD'] = 'password';

        try {
            (new DatabaseSeeder)->run();
            $this->fail('Production seeder accepted the default owner password.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('Set a non-default OWNER_PASSWORD', $exception->getMessage());
        } finally {
            putenv('OWNER_PASSWORD');
            unset($_ENV['OWNER_PASSWORD'], $_SERVER['OWNER_PASSWORD']);
            app()->detectEnvironment(fn () => $originalEnvironment);
        }
    }
}
