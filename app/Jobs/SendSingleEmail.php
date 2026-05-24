<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\EmailCampaign;
use App\Models\EmailMessage;
use App\Services\Email\CampaignCountRefresher;
use App\Services\Email\EmailPayload;
use App\Services\Email\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class SendSingleEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public int $recipientId) {}

    public function handle(EmailService $email): void
    {
        $send = DB::transaction(function (): ?array {
            $recipient = CampaignRecipient::query()->lockForUpdate()->findOrFail($this->recipientId);
            $campaign = $recipient->campaign;

            if ($recipient->provider_message_id || in_array($recipient->status, ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'skipped'], true)) {
                return null;
            }

            if (in_array($campaign->status, ['paused', 'cancelled'], true)) {
                return null;
            }

            $attempt = ((int) $recipient->attempt_count) + 1;
            $recipient->forceFill([
                'attempt_count' => $attempt,
                'last_attempt_at' => now(),
                'status' => 'sending',
            ])->save();

            return [
                'campaign_id' => $campaign->id,
                'contact_id' => $recipient->contact_id,
                'recipient_id' => $recipient->id,
                'email_normalized' => $recipient->email_normalized,
                'from_email' => $campaign->from_email,
                'from_name' => $campaign->from_name,
                'reply_to_email' => $campaign->reply_to_email,
                'provider' => $campaign->provider ?: config('emailora.provider'),
                'send_provider' => $campaign->provider === 'auto' ? null : $campaign->provider,
                'payload' => new EmailPayload(
                    to: $recipient->email_normalized,
                    subject: $recipient->personalized_subject ?: $campaign->subject,
                    html: $recipient->personalized_html ?: $campaign->html_body,
                    text: $recipient->personalized_text ?: $campaign->text_body,
                    fromEmail: $campaign->from_email,
                    fromName: $campaign->from_name,
                    replyTo: $campaign->reply_to_email,
                    tags: ['campaign_id' => $campaign->id, 'recipient_id' => $recipient->id],
                    idempotencyKey: "campaign:{$campaign->id}:recipient:{$recipient->id}",
                ),
            ];
        });

        if (! $send) {
            return;
        }

        $payload = $send['payload'];
        $result = $email->send($payload, $send['send_provider']);

        DB::transaction(function () use ($payload, $result, $send): void {
            $recipient = CampaignRecipient::query()->lockForUpdate()->findOrFail($send['recipient_id']);

            if ($recipient->provider_message_id || in_array($recipient->status, ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'skipped'], true)) {
                return;
            }

            EmailMessage::create([
                'email_campaign_id' => $send['campaign_id'],
                'campaign_recipient_id' => $send['recipient_id'],
                'contact_id' => $send['contact_id'],
                'email_normalized' => $send['email_normalized'],
                'from_email' => $send['from_email'],
                'from_name' => $send['from_name'],
                'reply_to_email' => $send['reply_to_email'],
                'subject' => $payload->subject,
                'html_body' => $payload->html,
                'text_body' => $payload->text,
                'provider' => $send['provider'],
                'provider_message_id' => $result->providerMessageId,
                'status' => $result->accepted ? 'sent' : 'failed',
                'provider_response' => $result->response,
                'error_message' => $result->errorMessage,
                'sent_at' => $result->accepted ? now() : null,
                'failed_at' => $result->accepted ? null : now(),
            ]);

            $recipient->update([
                'status' => $result->accepted ? 'sent' : 'failed',
                'provider' => $send['provider'],
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->response,
                'error_message' => $result->errorMessage,
                'sent_at' => $result->accepted ? now() : null,
                'failed_at' => $result->accepted ? null : now(),
            ]);
        });

        if ($campaign = EmailCampaign::find($send['campaign_id'])) {
            app(CampaignCountRefresher::class)->refresh($campaign);
        }
    }
}
