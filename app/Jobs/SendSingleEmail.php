<?php

namespace App\Jobs;

use App\Models\CampaignRecipient;
use App\Models\EmailMessage;
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
        DB::transaction(function () use ($email): void {
            $recipient = CampaignRecipient::query()->lockForUpdate()->findOrFail($this->recipientId);
            $campaign = $recipient->campaign;

            if ($recipient->provider_message_id || in_array($recipient->status, ['sent', 'delivered', 'opened', 'clicked', 'bounced', 'complained', 'skipped'], true)) {
                return;
            }

            if (in_array($campaign->status, ['paused', 'cancelled'], true)) {
                return;
            }

            $recipient->increment('attempt_count');
            $recipient->forceFill(['last_attempt_at' => now()])->save();

            $payload = new EmailPayload(
                to: $recipient->email_normalized,
                subject: $recipient->personalized_subject ?: $campaign->subject,
                html: $recipient->personalized_html ?: $campaign->html_body,
                text: $recipient->personalized_text ?: $campaign->text_body,
                fromEmail: $campaign->from_email,
                fromName: $campaign->from_name,
                replyTo: $campaign->reply_to_email,
                tags: ['campaign_id' => $campaign->id, 'recipient_id' => $recipient->id],
                idempotencyKey: "campaign:{$campaign->id}:recipient:{$recipient->id}:attempt:{$recipient->attempt_count}",
            );

            $result = $email->send($payload, $campaign->provider === 'auto' ? null : $campaign->provider);

            EmailMessage::create([
                'email_campaign_id' => $campaign->id,
                'campaign_recipient_id' => $recipient->id,
                'contact_id' => $recipient->contact_id,
                'email_normalized' => $recipient->email_normalized,
                'from_email' => $campaign->from_email,
                'from_name' => $campaign->from_name,
                'reply_to_email' => $campaign->reply_to_email,
                'subject' => $payload->subject,
                'html_body' => $payload->html,
                'text_body' => $payload->text,
                'provider' => $campaign->provider ?: config('emailora.provider'),
                'provider_message_id' => $result->providerMessageId,
                'status' => $result->accepted ? 'sent' : 'failed',
                'provider_response' => $result->response,
                'error_message' => $result->errorMessage,
                'sent_at' => $result->accepted ? now() : null,
                'failed_at' => $result->accepted ? null : now(),
            ]);

            $recipient->update([
                'status' => $result->accepted ? 'sent' : 'failed',
                'provider' => $campaign->provider ?: config('emailora.provider'),
                'provider_message_id' => $result->providerMessageId,
                'provider_response' => $result->response,
                'error_message' => $result->errorMessage,
                'sent_at' => $result->accepted ? now() : null,
                'failed_at' => $result->accepted ? null : now(),
            ]);
        });

        RefreshCampaignCounts::dispatch(CampaignRecipient::find($this->recipientId)?->email_campaign_id)->onQueue('email');
    }
}
