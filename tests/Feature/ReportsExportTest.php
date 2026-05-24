<?php

namespace Tests\Feature;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_period_filter_excludes_old_messages_campaigns_and_events(): void
    {
        Contact::factory()->create(['status' => 'active']);
        EmailMessage::query()->create([
            'email_normalized' => 'new@example.com',
            'from_email' => 'team@example.com',
            'subject' => 'New',
            'status' => 'delivered',
            'sent_at' => now(),
            'delivered_at' => now(),
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);
        EmailMessage::query()->create([
            'email_normalized' => 'old@example.com',
            'from_email' => 'team@example.com',
            'subject' => 'Old',
            'status' => 'failed',
            'created_at' => now()->subDays(45),
            'updated_at' => now()->subDays(45),
        ]);
        EmailCampaign::factory()->create(['status' => 'completed', 'created_at' => now()->subDays(2)]);
        EmailCampaign::factory()->create(['status' => 'draft', 'created_at' => now()->subDays(45)]);
        EmailEvent::query()->create([
            'provider' => 'brevo',
            'provider_event_id' => 'evt-new',
            'event_type' => 'delivered',
            'payload' => [],
            'occurred_at' => now()->subDays(2),
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.index', ['period' => '30']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Reports/Index')
                ->where('filters.period', '30')
                ->where('stats.messages', 1)
                ->where('stats.failed_messages', 0)
                ->where('stats.campaigns', 1)
                ->where('eventsByType.0.event_type', 'delivered'));
    }

    public function test_invalid_reports_period_falls_back_to_30_days(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('reports.index', ['period' => 'invalid']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('filters.period', '30'));
    }

    public function test_campaign_report_route_and_export_return_recipient_statuses(): void
    {
        $campaign = EmailCampaign::factory()->create(['name' => 'Report Campaign']);
        CampaignRecipient::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'sent@example.com',
            'status' => 'sent',
            'provider_message_id' => 'provider-1',
        ]);
        CampaignRecipient::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'failed@example.com',
            'status' => 'failed',
            'error_message' => 'Rejected',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('reports.campaign', $campaign))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Campaigns/Report')
                ->has('breakdown', 2));

        $this->actingAs(User::factory()->create())
            ->get(route('reports.campaign.export', $campaign))
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Disposition', 'attachment; filename=campaign-report.csv');
    }
}
