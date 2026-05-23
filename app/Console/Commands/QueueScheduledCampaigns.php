<?php

namespace App\Console\Commands;

use App\Jobs\PrepareEmailCampaignRecipients;
use App\Models\EmailCampaign;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('emailora:campaigns:queue-scheduled')]
#[Description('Queue due scheduled campaigns for recipient preparation and delivery.')]
class QueueScheduledCampaigns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;

        EmailCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->chunkById(100, function ($campaigns) use (&$count): void {
                foreach ($campaigns as $campaign) {
                    $campaign->update(['status' => 'queued']);
                    PrepareEmailCampaignRecipients::dispatch($campaign->id, $campaign->recipient_mode ?: 'current_audience')->onQueue('email');
                    $count++;
                }
            });

        $this->info("Queued {$count} scheduled campaigns.");

        return self::SUCCESS;
    }
}
