<?php

namespace Tests\Feature;

use App\Jobs\PrepareEmailCampaignRecipients;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Services\Email\AudienceResolver;
use App\Services\Email\CampaignCountRefresher;
use App\Services\Email\EmailPersonalizer;
use App\Services\Email\EmailPreviewDocument;
use App\Services\Email\UnsubscribeLinkBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class CampaignPersonalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_prepare_recipients_renders_blade_style_variables_and_unsubscribe_url(): void
    {
        Queue::fake();

        Contact::query()->create([
            'first_name' => 'Meven',
            'last_name' => 'Perera',
            'full_name' => 'Meven Perera',
            'email' => 'meven@example.com',
            'company' => 'CCA',
            'status' => 'active',
            'metadata' => ['student_id' => 'CCA-100'],
        ]);
        $campaign = EmailCampaign::factory()->create([
            'subject' => 'Hi {{ name }}',
            'preheader' => 'Payment due for {{ name }}',
            'html_body' => '<p>Hello {{ name }}</p><p>{{ metadata.student_id }}</p><p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>',
            'text_body' => 'Hello {{ name }} {{ unsubscribe_url }}',
            'target_type' => 'all_contacts',
            'status' => 'draft',
        ]);

        (new PrepareEmailCampaignRecipients($campaign->id))->handle(
            app(AudienceResolver::class),
            app(EmailPersonalizer::class),
            app(UnsubscribeLinkBuilder::class),
            app(EmailPreviewDocument::class),
            app(CampaignCountRefresher::class),
        );

        $recipient = $campaign->recipients()->firstOrFail();
        $this->assertSame('Hi Meven Perera', $recipient->personalized_subject);
        $this->assertStringContainsString('Payment due for Meven Perera', $recipient->personalized_html);
        $this->assertStringContainsString('Hello Meven Perera', $recipient->personalized_html);
        $this->assertStringContainsString('CCA-100', $recipient->personalized_html);
        $this->assertStringContainsString('/unsubscribe/', $recipient->personalized_html);
        $this->assertStringNotContainsString('{{ unsubscribe_url }}', $recipient->personalized_html);
    }

    public function test_unsubscribe_links_are_signed_without_expiry(): void
    {
        $campaign = EmailCampaign::factory()->create();
        $recipient = $campaign->recipients()->create([
            'email_normalized' => 'student@example.com',
            'status' => 'pending',
        ]);

        $url = app(UnsubscribeLinkBuilder::class)->forRecipient($recipient);

        $this->assertStringContainsString('/unsubscribe/', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringNotContainsString('expires=', $url);
    }
}
