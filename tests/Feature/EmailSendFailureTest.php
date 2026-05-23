<?php

namespace Tests\Feature;

use App\Jobs\SendSingleEmail;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailMessage;
use App\Services\Email\BrevoProvider;
use App\Services\Email\EmailPayload;
use App\Services\Email\EmailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
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

    public function test_brevo_payload_omits_empty_optional_fields(): void
    {
        Config::set('emailora.brevo.api_key', 'test-brevo-key');
        Http::preventStrayRequests();
        Http::fake([
            'https://api.brevo.com/v3/smtp/email' => Http::response(['messageId' => 'brevo-message-1'], 201),
        ]);

        $result = app(BrevoProvider::class)->send(new EmailPayload(
            to: 'sayuru555@gmail.com',
            subject: 'Test email',
            html: '<p>Hello Sayuru</p>',
            text: 'Hello Sayuru',
            fromEmail: 'team@codezela.com',
            fromName: 'Codezela Technologies',
        ));

        $this->assertTrue($result->accepted);

        Http::assertSent(function ($request) {
            $payload = $request->data();

            return $request->url() === 'https://api.brevo.com/v3/smtp/email'
                && $payload['sender'] === ['name' => 'Codezela Technologies', 'email' => 'team@codezela.com']
                && $payload['to'] === [['email' => 'sayuru555@gmail.com']]
                && $payload['subject'] === 'Test email'
                && $payload['htmlContent'] === '<p>Hello Sayuru</p>'
                && $payload['textContent'] === 'Hello Sayuru'
                && ! array_key_exists('replyTo', $payload)
                && ! array_key_exists('headers', $payload)
                && ! array_key_exists('tags', $payload);
        });
    }
}
