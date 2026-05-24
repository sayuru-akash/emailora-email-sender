<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\Email\CampaignCountRefresher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FinalizeEmailCampaign implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $campaignId) {}

    public function handle(CampaignCountRefresher $refresher): void
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);
        if (in_array($campaign->status, ['paused', 'cancelled'], true)) {
            return;
        }

        $campaign = $refresher->refresh($campaign);
        $unfinished = $campaign->recipients()->whereIn('status', ['pending', 'queued', 'sending'])->exists();

        if ($unfinished) {
            self::dispatch($campaign->id)->delay(now()->addSeconds(10))->onQueue('email');

            return;
        }

        $successful = $campaign->recipients()->whereIn('status', ['sent', 'delivered', 'opened', 'clicked'])->exists();
        $campaign->update(['status' => $successful ? 'completed' : 'failed', 'completed_at' => now()]);
    }
}
