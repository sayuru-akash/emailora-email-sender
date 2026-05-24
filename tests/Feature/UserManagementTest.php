<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_filter_update_and_delete_users(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->post(route('users.store'), [
                'name' => 'Staff User',
                'email' => 'staff@example.com',
                'role' => 'staff',
                'status' => 'active',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('users.index'));

        $staff = User::query()->where('email', 'staff@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $staff->password));

        $this->actingAs($owner)
            ->get(route('users.index', ['search' => 'staff', 'role' => 'staff', 'status' => 'active']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Users/Index')
                ->where('users.meta.total', 1)
                ->where('users.data.0.email', 'staff@example.com'));

        $oldPassword = $staff->password;
        $this->actingAs($owner)
            ->put(route('users.update', $staff), [
                'name' => 'Updated Staff',
                'email' => 'staff@example.com',
                'role' => 'manager',
                'status' => 'inactive',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect();

        $staff->refresh();
        $this->assertSame('manager', $staff->role);
        $this->assertSame('inactive', $staff->status);
        $this->assertSame($oldPassword, $staff->password);

        $this->actingAs($owner)->delete(route('users.destroy', $staff))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $staff->id]);
    }

    public function test_admin_cannot_edit_or_delete_owner_or_admin_users(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($admin)
            ->put(route('users.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => 'owner',
                'status' => 'active',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Admins cannot edit owner or admin users.');

        $this->actingAs($admin)
            ->delete(route('users.destroy', $owner))
            ->assertRedirect()
            ->assertSessionHas('error', 'Admins cannot delete owner or admin users.');

        $this->assertNotNull($owner->fresh());
    }

    public function test_user_cannot_delete_self(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);

        $this->actingAs($owner)
            ->delete(route('users.destroy', $owner))
            ->assertRedirect()
            ->assertSessionHas('error', 'Users cannot delete themselves.');

        $this->assertNotNull($owner->fresh());
    }
}
