<?php

namespace Tests\Feature;

use App\Jobs\PrepareEmailCampaignRecipients;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use App\Models\ListModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignAudienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_audience_contacts_returns_only_emailable_search_results(): void
    {
        Contact::factory()->create(['full_name' => 'Target Student', 'email' => 'target@example.com', 'status' => 'active']);
        Contact::factory()->create(['full_name' => 'Blocked Student', 'email' => 'blocked@example.com', 'status' => 'blocked']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('campaigns.audience.contacts', ['search' => 'Student']))
            ->assertOk()
            ->assertJsonCount(1, 'contacts')
            ->assertJsonPath('contacts.0.email', 'target@example.com');
    }

    public function test_audience_estimate_counts_sendable_and_suppressed_contacts(): void
    {
        $sendable = Contact::factory()->create(['status' => 'active']);
        $suppressed = Contact::factory()->create(['email' => 'suppressed@example.com', 'email_normalized' => 'suppressed@example.com', 'status' => 'active']);
        EmailSuppression::query()->create(['email_normalized' => $suppressed->email_normalized, 'reason' => 'bounce']);
        $list = ListModel::factory()->create();
        $list->contacts()->attach([$sendable->id, $suppressed->id]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('campaigns.audience.estimate'), [
                'target_type' => 'list',
                'target_filters' => ['list_ids' => [$list->id]],
            ])
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('suppressed_count', 1)
            ->assertJsonPath('sendable_count', 1);
    }

    public function test_send_rejects_empty_audience(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'target_type' => 'manual_selection',
            'target_filters' => ['contact_ids' => []],
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign), ['recipient_mode' => 'current_audience'])
            ->assertSessionHasErrors('campaign');

        $this->assertSame('draft', $campaign->refresh()->status);
    }

    public function test_scheduled_send_does_not_dispatch_prepare_job_until_scheduler_runs(): void
    {
        Queue::fake();

        $contact = Contact::factory()->create(['status' => 'active']);
        $campaign = EmailCampaign::factory()->create([
            'target_type' => 'manual_selection',
            'target_filters' => ['contact_ids' => [$contact->id]],
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign), [
                'recipient_mode' => 'current_audience',
                'scheduled_at' => now()->addHour()->toDateTimeString(),
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Campaign scheduled.');

        $this->assertSame('scheduled', $campaign->refresh()->status);
        Queue::assertNotPushed(PrepareEmailCampaignRecipients::class);
    }
}
