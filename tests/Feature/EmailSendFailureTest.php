<?php

namespace Tests\Feature;

use App\Jobs\SendSingleEmail;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailMessage;
use App\Services\Email\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailSendFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_provider_key_persists_failure_instead_of_fake_success(): void
    {
        Queue::fake();
        Config::set('emailora.resend.api_key', null);

        $contact = Contact::factory()->create(['email' => 'jane@example.com']);
        $campaign = EmailCampaign::factory()->create(['provider' => 'resend']);
        $recipient = CampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'email_normalized' => $contact->email_normalized,
            'personalized_subject' => 'Hello Jane',
            'personalized_html' => '<p>Hello</p>',
            'personalized_text' => 'Hello',
            'status' => 'queued',
        ]);

        (new SendSingleEmail($recipient->id))->handle(app(EmailService::class));

        $recipient->refresh();
        $this->assertSame('failed', $recipient->status);
        $this->assertStringContainsString('API key is not configured', $recipient->error_message);
        $this->assertSame('failed', EmailMessage::first()->status);
    }
}
