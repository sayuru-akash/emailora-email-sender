<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use App\Services\Email\CampaignCountRefresher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshCampaignCounts implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $campaignId) {}

    public function handle(CampaignCountRefresher $refresher): void
    {
        if ($this->campaignId && $campaign = EmailCampaign::find($this->campaignId)) {
            $refresher->refresh($campaign);
        }
    }
}
