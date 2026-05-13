<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\Email\AudienceResolver;
use App\Services\Email\CampaignCountRefresher;
use App\Services\Email\EmailPersonalizer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PrepareEmailCampaignRecipients implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $campaignId) {}

    public function handle(AudienceResolver $resolver, EmailPersonalizer $personalizer, CampaignCountRefresher $counts): void
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);
        if (in_array($campaign->status, ['cancelled', 'paused', 'completed'], true)) {
            return;
        }

        $campaign->update(['status' => 'preparing']);

        $resolver->queryForCampaign($campaign)->chunkById(200, function ($contacts) use ($campaign, $personalizer): void {
            foreach ($contacts as $contact) {
                $campaign->recipients()->firstOrCreate(
                    ['email_normalized' => $contact->email_normalized],
                    [
                        'contact_id' => $contact->id,
                        'personalized_subject' => $personalizer->render($campaign->subject, $contact),
                        'personalized_html' => $campaign->html_body ? $personalizer->render($campaign->html_body, $contact) : null,
                        'personalized_text' => $campaign->text_body ? $personalizer->render($campaign->text_body, $contact) : null,
                        'status' => 'pending',
                    ]
                );
            }
        });

        $counts->refresh($campaign);
        SendEmailCampaignMessages::dispatch($campaign->id)->onQueue('email');
    }
}
