<?php

namespace Tests\Feature;

use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailSuppression;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class UnsubscribeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_unsubscribe_request_marks_recipient_contact_and_suppression(): void
    {
        $contact = Contact::factory()->create(['email' => 'student@example.com', 'email_normalized' => 'student@example.com']);
        $campaign = EmailCampaign::factory()->create();
        $recipient = CampaignRecipient::create([
            'email_campaign_id' => $campaign->id,
            'contact_id' => $contact->id,
            'email_normalized' => 'student@example.com',
            'status' => 'sent',
        ]);
        $token = base64_encode((string) $recipient->id);

        $this->post(URL::signedRoute('unsubscribe.store', ['signedToken' => $token]))
            ->assertRedirect(URL::signedRoute('unsubscribe.show', ['signedToken' => $token]));

        $this->assertSame('skipped', $recipient->refresh()->status);
        $this->assertSame('unsubscribed', $contact->refresh()->status);
        $this->assertDatabaseHas(EmailSuppression::class, [
            'email_normalized' => 'student@example.com',
            'reason' => 'unsubscribed',
            'email_campaign_id' => $campaign->id,
        ]);
    }

    public function test_tampered_unsubscribe_token_is_rejected(): void
    {
        $token = base64_encode('1');
        $url = URL::signedRoute('unsubscribe.store', ['signedToken' => $token]);

        $this->post(str_replace($token, base64_encode('2'), $url))
            ->assertForbidden();
    }
}
