<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\EmailEvent;
use App\Models\EmailMessage;
use App\Models\EmailSuppression;
use App\Services\Email\EmailWebhookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessEmailWebhookEvent implements ShouldQueue
{
    use Queueable;

    public function __construct(public EmailWebhookEvent $event) {}

    public function handle(): void
    {
        $eventId = $this->event->dedupeKey();
        $existing = EmailEvent::where('provider', $this->event->provider)->where('provider_event_id', $eventId)->first();

        if ($existing?->processed_at) {
            return;
        }

        $recipient = CampaignRecipient::where('provider_message_id', $this->event->providerMessageId)->first();
        $message = EmailMessage::where('provider_message_id', $this->event->providerMessageId)->first();
        $status = $this->mapStatus($this->event->eventType);

        $record = $existing ?: EmailEvent::create([
            'provider' => $this->event->provider,
            'provider_event_id' => $eventId,
            'provider_message_id' => $this->event->providerMessageId,
            'event_type' => $this->event->eventType,
            'email_campaign_id' => $recipient?->email_campaign_id,
            'campaign_recipient_id' => $recipient?->id,
            'email_message_id' => $message?->id,
            'contact_id' => $recipient?->contact_id,
            'email_normalized' => $recipient?->email_normalized ?: $this->event->email,
            'payload' => $this->event->sanitizedPayload(),
            'occurred_at' => $this->event->occurredAt,
        ]);

        if ($recipient && $status) {
            $recipient->update(['status' => $status, $status.'_at' => in_array($status, ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'failed'], true) ? now() : null]);
        }

        if ($message && $status) {
            $message->update(['status' => $status, $status.'_at' => in_array($status, ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'failed'], true) ? now() : null]);
        }

        if ($recipient && in_array($status, ['bounced', 'complained'], true)) {
            $recipient->contact?->update(['status' => $status, $status.'_at' => now()]);
            EmailSuppression::updateOrCreate(['email_normalized' => $recipient->email_normalized], ['reason' => $status, 'provider' => $this->event->provider, 'email_campaign_id' => $recipient->email_campaign_id]);
        }

        $record->update(['processed_at' => now()]);
        if ($recipient) {
            RefreshCampaignCounts::dispatch($recipient->email_campaign_id)->onQueue('email');
        }
    }

    private function mapStatus(string $event): ?string
    {
        return match (strtolower($event)) {
            'sent' => 'sent',
            'delivered' => 'delivered',
            'opened', 'open' => 'opened',
            'clicked', 'click' => 'clicked',
            'bounced', 'hard_bounce' => 'bounced',
            'complained', 'spam', 'complaint' => 'complained',
            'failed', 'error', 'blocked', 'suppressed' => 'failed',
            default => null,
        };
    }
}
