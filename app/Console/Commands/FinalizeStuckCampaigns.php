<?php

namespace App\Console\Commands;

use App\Jobs\FinalizeEmailCampaign;
use App\Models\EmailCampaign;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('emailora:campaigns:finalize-stuck')]
#[Description('Queue finalization checks for stale active campaigns.')]
class FinalizeStuckCampaigns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;

        EmailCampaign::whereIn('status', ['queued', 'preparing', 'sending'])
            ->where('updated_at', '<', now()->subMinutes(10))
            ->each(function (EmailCampaign $campaign) use (&$count): void {
                FinalizeEmailCampaign::dispatch($campaign->id)->onQueue('email');
                $count++;
            });

        $this->info("Queued {$count} finalization checks.");

        return self::SUCCESS;
    }
}
