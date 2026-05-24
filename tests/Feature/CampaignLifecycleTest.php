<?php

namespace Tests\Feature;

use App\Jobs\PrepareEmailCampaignRecipients;
use App\Jobs\SendEmailCampaignMessages;
use App\Jobs\SendSingleEmail;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\ListModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_campaign_show_exposes_edit_and_send_actions(): void
    {
        $campaign = EmailCampaign::factory()->create(['status' => 'draft']);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.show', $campaign))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('actions.canEdit', true)
                ->where('actions.canSend', true)
                ->where('actions.canPause', false)
                ->where('actions.canDelete', true));
    }

    public function test_active_campaign_edit_request_redirects_to_show_with_a_friendly_error(): void
    {
        $user = User::factory()->create();
        $campaign = EmailCampaign::factory()->create(['status' => 'sending']);

        $this->actingAs($user)
            ->get(route('campaigns.edit', $campaign))
            ->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('error', 'Only draft or scheduled campaigns can be edited. Duplicate this campaign to make changes.');
    }

    public function test_active_campaign_update_request_redirects_to_show_with_a_friendly_error(): void
    {
        $user = User::factory()->create();
        $campaign = EmailCampaign::factory()->create(['status' => 'sending']);

        $this->actingAs($user)
            ->put(route('campaigns.update', $campaign), [
                'name' => $campaign->name,
                'subject' => $campaign->subject,
                'from_name' => $campaign->from_name,
                'from_email' => $campaign->from_email,
                'html_body' => $campaign->html_body,
                'text_body' => $campaign->text_body,
                'target_type' => $campaign->target_type,
                'status' => 'draft',
            ])
            ->assertRedirect(route('campaigns.show', $campaign))
            ->assertSessionHas('error', 'Only draft or scheduled campaigns can be edited. Duplicate this campaign to make changes.');
    }

    public function test_active_campaigns_cannot_be_deleted(): void
    {
        $user = User::factory()->create();
        $campaign = EmailCampaign::factory()->create(['status' => 'sending']);

        $this->actingAs($user)->delete(route('campaigns.destroy', $campaign))->assertStatus(422);
    }

    public function test_send_rejects_unknown_variables_but_allows_supported_variables(): void
    {
        Queue::fake();
        Contact::query()->create([
            'full_name' => 'Student One',
            'email' => 'student@example.com',
            'status' => 'active',
        ]);
        $campaign = EmailCampaign::factory()->create([
            'subject' => 'Hi {{ name }} {{ unknown }}',
            'html_body' => '<p>{{ name }}</p><p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>',
            'target_type' => 'all_contacts',
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign))
            ->assertSessionHasErrors('campaign');

        $campaign->update([
            'subject' => 'Hi {{ name }}',
            'html_body' => '<p>You can unsubscribe by replying.</p>',
            'text_body' => null,
        ]);
        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign), ['recipient_mode' => 'new_contacts'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        Queue::assertPushed(PrepareEmailCampaignRecipients::class, fn (PrepareEmailCampaignRecipients $job): bool => $job->campaignId === $campaign->id && $job->recipientMode === 'new_contacts');
        $this->assertStringContainsString('{{ unsubscribe_url }}', $campaign->refresh()->html_body);

        $campaign->update(['status' => 'draft']);
        $campaign->update(['html_body' => '<p>{{ name }}</p><p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>']);
        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign))
            ->assertSessionHasNoErrors()
            ->assertRedirect();
    }

    public function test_campaign_store_appends_unsubscribe_footer_when_body_only_mentions_token_name(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.store'), [
                'name' => 'Footer guard',
                'subject' => 'Hello students',
                'from_name' => 'Emailora',
                'from_email' => 'sender@example.com',
                'html_body' => '<p>This body mentions unsubscribe_url but has no unsubscribe token.</p>',
                'text_body' => 'This body already has {unsubscribe_url}.',
                'provider' => 'brevo',
                'target_type' => 'all_contacts',
                'status' => 'draft',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $campaign = EmailCampaign::query()->where('name', 'Footer guard')->firstOrFail();

        $this->assertStringContainsString('mentions unsubscribe_url', $campaign->html_body);
        $this->assertStringContainsString('<a href="{{ unsubscribe_url }}">Unsubscribe</a>', $campaign->html_body);
        $this->assertSame('This body already has {unsubscribe_url}.', $campaign->text_body);
    }

    public function test_send_appends_unsubscribe_footer_to_each_non_empty_body_part_that_lacks_a_token(): void
    {
        Queue::fake();
        Contact::factory()->create(['status' => 'active']);
        $campaign = EmailCampaign::factory()->create([
            'html_body' => '<p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>',
            'text_body' => 'Plain-text copy without a link.',
            'target_type' => 'all_contacts',
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $campaign->refresh();

        $this->assertSame('<p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>', $campaign->html_body);
        $this->assertStringContainsString('Plain-text copy without a link.', $campaign->text_body);
        $this->assertStringContainsString('Unsubscribe: {{ unsubscribe_url }}', $campaign->text_body);
    }

    public function test_preheader_unsubscribe_token_does_not_suppress_visible_body_footer(): void
    {
        Queue::fake();
        Contact::factory()->create(['status' => 'active']);
        $campaign = EmailCampaign::factory()->create([
            'preheader' => '{{ unsubscribe_url }}',
            'html_body' => '<p>Body copy without a visible unsubscribe token.</p>',
            'text_body' => '',
            'target_type' => 'all_contacts',
            'status' => 'draft',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.send', $campaign))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertStringContainsString('<a href="{{ unsubscribe_url }}">Unsubscribe</a>', $campaign->refresh()->html_body);
    }

    public function test_pause_resume_and_cancel_are_status_guarded(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $draft = EmailCampaign::factory()->create(['status' => 'draft']);
        $queued = EmailCampaign::factory()->create(['status' => 'queued']);

        $this->actingAs($user)->post(route('campaigns.pause', $draft))->assertStatus(422);
        $this->actingAs($user)->post(route('campaigns.pause', $queued))->assertRedirect();
        $this->assertSame('paused', $queued->refresh()->status);

        $this->actingAs($user)->post(route('campaigns.resume', $draft))->assertStatus(422);
        $this->actingAs($user)->post(route('campaigns.resume', $queued))->assertRedirect();
        $this->assertSame('queued', $queued->refresh()->status);

        $this->actingAs($user)->post(route('campaigns.cancel', $draft))->assertStatus(422);
        $this->actingAs($user)->post(route('campaigns.cancel', $queued))->assertRedirect();
        $this->assertSame('cancelled', $queued->refresh()->status);
    }

    public function test_queued_campaign_index_exposes_target_audience_count_before_recipients_are_prepared(): void
    {
        $list = ListModel::query()->create(['name' => 'Customers', 'slug' => 'customers']);
        $contact = Contact::factory()->create();
        $list->contacts()->attach($contact);
        $campaign = EmailCampaign::factory()->create([
            'status' => 'queued',
            'target_type' => 'list',
            'target_filters' => ['list_ids' => [$list->id]],
            'total_recipients' => 0,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('campaigns.data.0.id', $campaign->id)
                ->where('campaigns.data.0.display_recipient_count', 1)
                ->where('campaigns.data.0.has_prepared_recipients', false));
    }

    public function test_queued_campaign_recipients_show_target_audience_before_recipients_are_prepared(): void
    {
        $list = ListModel::query()->create(['name' => 'Customers', 'slug' => 'customers']);
        $contact = Contact::factory()->create([
            'email' => 'customer@example.com',
            'email_normalized' => 'customer@example.com',
        ]);
        $list->contacts()->attach($contact);
        $campaign = EmailCampaign::factory()->create([
            'status' => 'queued',
            'target_type' => 'list',
            'target_filters' => ['list_ids' => [$list->id]],
            'total_recipients' => 0,
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.recipients', $campaign))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('mode', 'target_audience')
                ->where('recipients.data.0.email_normalized', 'customer@example.com')
                ->where('recipients.data.0.status', 'targeted'));
    }

    public function test_paused_campaigns_cannot_be_deleted_directly(): void
    {
        $campaign = EmailCampaign::factory()->create(['status' => 'paused']);

        $this->actingAs(User::factory()->create())
            ->delete(route('campaigns.destroy', $campaign))
            ->assertStatus(422);

        $this->assertModelExists($campaign);
    }

    public function test_duplicate_resets_delivery_state_and_uses_current_user(): void
    {
        $user = User::factory()->create();
        $campaign = EmailCampaign::factory()->create([
            'status' => 'completed',
            'scheduled_at' => now()->subDay(),
            'started_at' => now()->subDay(),
            'completed_at' => now(),
            'total_recipients' => 20,
            'sent_count' => 18,
            'failed_count' => 2,
            'approved_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($user)
            ->post(route('campaigns.duplicate', $campaign))
            ->assertRedirect();

        $copy = EmailCampaign::query()->whereKeyNot($campaign->id)->firstOrFail();
        $this->assertSame('draft', $copy->status);
        $this->assertNull($copy->scheduled_at);
        $this->assertNull($copy->started_at);
        $this->assertNull($copy->completed_at);
        $this->assertSame(0, $copy->total_recipients);
        $this->assertSame(0, $copy->sent_count);
        $this->assertSame(0, $copy->failed_count);
        $this->assertNull($copy->approved_by);
        $this->assertSame($user->id, $copy->created_by);
    }

    public function test_resend_failed_only_requeues_failed_ids_on_terminal_campaigns(): void
    {
        Queue::fake();
        $campaign = EmailCampaign::factory()->create(['status' => 'completed']);
        $failed = CampaignRecipient::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'failed@example.com',
            'status' => 'failed',
            'error_message' => 'Provider rejected',
        ]);
        $queued = CampaignRecipient::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'queued@example.com',
            'status' => 'queued',
        ]);
        $campaign->forceFill(['failed_count' => 1, 'queued_count' => 1])->save();

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.resend-failed', $campaign))
            ->assertRedirect();

        Queue::assertPushed(SendSingleEmail::class, fn (SendSingleEmail $job): bool => $job->recipientId === $failed->id);
        Queue::assertNotPushed(SendSingleEmail::class, fn (SendSingleEmail $job): bool => $job->recipientId === $queued->id);
        $this->assertSame('queued', $failed->refresh()->status);
    }

    public function test_resend_failed_is_blocked_for_non_terminal_campaigns(): void
    {
        $campaign = EmailCampaign::factory()->create(['status' => 'paused', 'failed_count' => 1]);

        $this->actingAs(User::factory()->create())
            ->post(route('campaigns.resend-failed', $campaign))
            ->assertStatus(422);
    }

    public function test_due_scheduled_campaigns_are_queued_by_command(): void
    {
        Queue::fake();
        $due = EmailCampaign::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->subMinute(),
        ]);
        $future = EmailCampaign::factory()->create([
            'status' => 'scheduled',
            'scheduled_at' => now()->addHour(),
        ]);

        $this->artisan('emailora:campaigns:queue-scheduled')
            ->expectsOutput('Queued 1 scheduled campaigns.')
            ->assertSuccessful();

        $this->assertSame('queued', $due->refresh()->status);
        $this->assertSame('scheduled', $future->refresh()->status);
        Queue::assertPushed(PrepareEmailCampaignRecipients::class, fn (PrepareEmailCampaignRecipients $job): bool => $job->campaignId === $due->id);
    }

    public function test_recover_prepares_active_campaigns_without_prepared_recipients(): void
    {
        Queue::fake();
        $campaign = EmailCampaign::factory()->create(['status' => 'queued']);

        $this->artisan('emailora:campaigns:recover')->assertSuccessful();

        Queue::assertPushed(PrepareEmailCampaignRecipients::class, fn (PrepareEmailCampaignRecipients $job): bool => $job->campaignId === $campaign->id);
    }

    public function test_recover_resumes_active_campaigns_with_pending_recipients(): void
    {
        Queue::fake();
        $campaign = EmailCampaign::factory()->create(['status' => 'sending']);
        CampaignRecipient::query()->create([
            'email_campaign_id' => $campaign->id,
            'email_normalized' => 'pending@example.com',
            'status' => 'pending',
        ]);

        $this->artisan('emailora:campaigns:recover')->assertSuccessful();

        Queue::assertPushed(SendEmailCampaignMessages::class, fn (SendEmailCampaignMessages $job): bool => $job->campaignId === $campaign->id);
    }
}
