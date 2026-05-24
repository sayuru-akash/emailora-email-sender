<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\User;
use App\Services\Activity\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_observer_logs_contact_create_update_and_delete_with_safe_summary(): void
    {
        $contact = Contact::factory()->create(['email' => 'student@example.com']);
        $contact->update(['status' => 'blocked']);
        $contact->delete();

        $this->assertDatabaseHas(ActivityLog::class, ['event' => 'Contact.created', 'subject_id' => $contact->id]);
        $this->assertDatabaseHas(ActivityLog::class, ['event' => 'Contact.updated', 'subject_id' => $contact->id]);
        $this->assertDatabaseHas(ActivityLog::class, ['event' => 'Contact.deleted', 'subject_id' => $contact->id]);

        $log = ActivityLog::where('event', 'Contact.created')->firstOrFail();
        $this->assertArrayHasKey('summary', $log->properties);
        $this->assertArrayNotHasKey('metadata', $log->properties['summary']);
    }

    public function test_activity_logger_sanitizes_sensitive_nested_properties_and_query_strings(): void
    {
        $this->get('/activity-logs?token=secret-value');

        $log = app(ActivityLogger::class)->log('security.checked', 'Security check.', null, [
            'token' => 'secret',
            'nested' => [
                'api_key' => 'secret',
                'safe' => 'kept',
                'payload' => ['hidden' => true],
            ],
        ], 'system');

        $this->assertArrayNotHasKey('token', $log->properties);
        $this->assertArrayNotHasKey('api_key', $log->properties['nested']);
        $this->assertArrayNotHasKey('payload', $log->properties['nested']);
        $this->assertSame('kept', $log->properties['nested']['safe']);
        $this->assertSame('/activity-logs', $log->url);
    }

    public function test_activity_logs_index_filters_and_export_for_admins(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        ActivityLog::factory()->create([
            'category' => 'imports',
            'event' => 'import.completed',
            'severity' => 'info',
            'user_id' => $admin->id,
            'description' => 'Import finished',
        ]);
        ActivityLog::factory()->create([
            'category' => 'campaigns',
            'event' => 'campaign.queued',
            'severity' => 'warning',
            'user_id' => $staff->id,
            'description' => 'Campaign queued',
        ]);

        $this->actingAs($admin)
            ->get(route('activity-logs.index', ['category' => 'imports', 'search' => 'finished']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('ActivityLogs/Index')
                ->where('activities.meta.total', 1));

        $this->actingAs($admin)
            ->get(route('activity-logs.export', ['category' => 'imports']))
            ->assertOk()
            ->assertStreamed();

        $this->actingAs($staff)
            ->get(route('activity-logs.index'))
            ->assertForbidden();
    }
}
