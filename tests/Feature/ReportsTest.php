<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_index_returns_real_campaign_contact_message_and_event_metrics(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['status' => 'active', 'source' => 'manual']);
        Contact::factory()->create(['status' => 'unsubscribed', 'source' => 'import']);
        $campaign = EmailCampaign::factory()->create([
            'name' => 'Report Campaign',
            'status' => 'completed',
            'total_recipients' => 10,
            'sent_count' => 8,
            'delivered_count' => 6,
            'opened_count' => 3,
            'clicked_count' => 2,
            'failed_count' => 1,
            'created_at' => now(),
        ]);
        EmailMessage::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'student@example.com',
            'from_email' => 'team@example.com',
            'subject' => 'Sent message',
            'status' => 'delivered',
            'sent_at' => now(),
            'delivered_at' => now(),
            'opened_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        EmailMessage::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'failed@example.com',
            'from_email' => 'team@example.com',
            'subject' => 'Failed message',
            'status' => 'failed',
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        EmailEvent::query()->create([
            'provider' => 'brevo',
            'provider_event_id' => 'evt-report-1',
            'event_type' => 'delivered',
            'payload' => [],
            'occurred_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('reports.index', ['period' => '30']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('filters.period', '30')
                ->where('stats.contacts', 2)
                ->where('stats.active_contacts', 1)
                ->where('stats.unsubscribed_contacts', 1)
                ->where('stats.messages', 2)
                ->where('stats.failed_messages', 1)
                ->where('rates.delivery', 100)
                ->has('contactsByStatus', 2)
                ->has('contactsBySource', 2)
                ->has('campaignsByStatus', 1)
                ->has('messagesByStatus', 2)
                ->has('eventsByType', 1)
                ->has('messageTimeline', 1)
                ->has('topCampaigns', 1)
                ->has('recentCampaigns', 1));
    }
}
