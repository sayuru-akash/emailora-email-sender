<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_email_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Index')
                ->has('providerStatus.resend')
                ->has('providerStatus.brevo'));

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => 'Emailora',
                'timezone' => 'Asia/Colombo',
                'default_from_name' => 'Codezela Technologies',
                'default_from_email' => 'team@codezela.com',
                'default_reply_to' => 'team@codezela.com',
                'default_provider' => 'brevo',
                'fallback_provider' => 'resend',
                'rate_limit_per_minute' => 300,
                'chunk_size' => 50,
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Settings updated.');

        $this->assertSame('brevo', SystemSetting::where('key', 'default_provider')->firstOrFail()->value);
    }

    public function test_staff_cannot_view_update_settings_or_send_test_email(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        SystemSetting::query()->create([
            'key' => 'resend_api_key',
            'group' => 'email',
            'value' => 'secret-value',
        ]);

        $this->actingAs($staff)
            ->get(route('settings.index'))
            ->assertForbidden();

        $this->actingAs($staff)
            ->put(route('settings.update'), [
                'company_name' => 'Emailora',
                'timezone' => 'Asia/Colombo',
                'default_provider' => 'brevo',
                'rate_limit_per_minute' => 300,
                'chunk_size' => 50,
            ])
            ->assertForbidden();

        $this->actingAs($staff)
            ->post(route('settings.test-email'), [
                'to' => 'test@example.com',
                'provider' => 'brevo',
            ])
            ->assertForbidden();
    }

    public function test_settings_validation_rejects_bad_provider_rate_limit_and_emails(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->put(route('settings.update'), [
                'company_name' => 'Emailora',
                'timezone' => 'not-a-timezone',
                'default_from_email' => 'not-email',
                'default_reply_to' => 'not-email',
                'default_provider' => 'bad-provider',
                'rate_limit_per_minute' => 0,
                'chunk_size' => 0,
            ])
            ->assertSessionHasErrors([
                'timezone',
                'default_from_email',
                'default_reply_to',
                'default_provider',
                'rate_limit_per_minute',
                'chunk_size',
            ]);
    }

    public function test_admin_test_email_records_provider_success_flash(): void
    {
        Http::fake([
            'api.resend.com/*' => Http::response(['id' => 'msg_test'], 200),
        ]);
        config(['emailora.resend.api_key' => 'test-resend-key']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.test-email'), [
                'to' => 'test@example.com',
                'provider' => 'resend',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Provider accepted the test email.');
    }

    public function test_admin_test_email_surfaces_provider_failure_without_success_flash(): void
    {
        Http::fake([
            'api.resend.com/*' => Http::response(['message' => 'bad sender'], 422),
        ]);
        config(['emailora.resend.api_key' => 'test-resend-key']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('settings.test-email'), [
                'to' => 'test@example.com',
                'provider' => 'resend',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'Resend rejected the email.')
            ->assertSessionMissing('success');
    }
}
