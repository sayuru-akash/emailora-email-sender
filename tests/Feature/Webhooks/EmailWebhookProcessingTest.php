<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\ProcessEmailWebhookEvent;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Services\Email\EmailWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailWebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_bounce_event_updates_recipient_message_contact_suppression_and_campaign_counts(): void
    {
        Queue::fake();
        $contact = Contact::factory()->create(['email' => 'student@example.com', 'email_normalized' => 'student@example.com']);
        $campaign = EmailCampaign::factory()->create(['status' => 'sending']);
        $recipient = CampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'email_normalized' => 'student@example.com',
            'status' => 'sent',
            'provider_message_id' => 'msg_1',
        ]);
        $message = EmailMessage::create([
            'email_campaign_id' => $campaign->id,
            'campaign_recipient_id' => $recipient->id,
            'contact_id' => $contact->id,
            'email_normalized' => 'student@example.com',
            'from_email' => 'team@example.com',
            'subject' => 'Update',
            'provider_message_id' => 'msg_1',
            'status' => 'sent',
        ]);

        (new ProcessEmailWebhookEvent(new EmailWebhookEvent('brevo', 'hard_bounce', 'evt_1', 'msg_1', 'student@example.com', [])))->handle();

        $this->assertSame('bounced', $recipient->refresh()->status);
        $this->assertSame('bounced', $message->refresh()->status);
        $this->assertSame('bounced', $contact->refresh()->status);
        $this->assertNotNull($recipient->bounced_at);
        $this->assertNotNull($message->bounced_at);
        $this->assertDatabaseHas(EmailSuppression::class, [
            'email_normalized' => 'student@example.com',
            'reason' => 'bounced',
            'provider' => 'brevo',
        ]);
        $this->assertDatabaseHas(EmailEvent::class, [
            'provider' => 'brevo',
            'provider_event_id' => 'evt_1',
            'event_type' => 'hard_bounce',
        ]);
    }

    public function test_duplicate_provider_event_is_idempotent(): void
    {
        (new ProcessEmailWebhookEvent(new EmailWebhookEvent('resend', 'delivered', 'evt_same', 'missing', 'student@example.com', [])))->handle();
        (new ProcessEmailWebhookEvent(new EmailWebhookEvent('resend', 'delivered', 'evt_same', 'missing', 'student@example.com', [])))->handle();

        $this->assertSame(1, EmailEvent::where('provider', 'resend')->where('provider_event_id', 'evt_same')->count());
    }
}
