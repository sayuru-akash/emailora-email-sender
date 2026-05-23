<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\Email\AudienceResolver;
use App\Services\Email\CampaignCountRefresher;
use App\Services\Email\EmailPersonalizer;
use App\Services\Email\EmailPreviewDocument;
use App\Services\Email\UnsubscribeLinkBuilder;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PrepareEmailCampaignRecipients implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(public int $campaignId, public string $recipientMode = 'current_audience') {}

    public function uniqueId(): string
    {
        return "{$this->campaignId}:{$this->recipientMode}";
    }

    public function handle(AudienceResolver $resolver, EmailPersonalizer $personalizer, UnsubscribeLinkBuilder $unsubscribeLinks, EmailPreviewDocument $preview, CampaignCountRefresher $counts): void
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);
        if (in_array($campaign->status, ['cancelled', 'paused', 'completed'], true)) {
            return;
        }

        $campaign->update(['status' => 'preparing']);

        if ($this->recipientMode === 'current_audience') {
            $campaign->recipients()
                ->whereNull('provider_message_id')
                ->whereIn('status', ['pending', 'queued', 'failed', 'skipped'])
                ->delete();
        }

        $resolver->queryForCampaign($campaign)->chunkById(200, function ($contacts) use ($campaign, $personalizer, $unsubscribeLinks, $preview): void {
            foreach ($contacts as $contact) {
                $recipient = $campaign->recipients()->firstOrCreate(
                    ['email_normalized' => $contact->email_normalized],
                    [
                        'contact_id' => $contact->id,
                        'status' => 'pending',
                    ]
                );

                $values = ['unsubscribe_url' => $unsubscribeLinks->forRecipient($recipient)];

                $recipient->update([
                    'contact_id' => $contact->id,
                    'personalized_subject' => $personalizer->render($campaign->subject, $contact, $values),
                    'personalized_html' => $campaign->html_body ? $personalizer->render($preview->withPreheader($campaign->html_body, $campaign->preheader), $contact, $values) : null,
                    'personalized_text' => $campaign->text_body ? $personalizer->render($campaign->text_body, $contact, $values) : null,
                ]);
            }
        });

        $counts->refresh($campaign);
        SendEmailCampaignMessages::dispatch($campaign->id)->onQueue('email');
    }
}
