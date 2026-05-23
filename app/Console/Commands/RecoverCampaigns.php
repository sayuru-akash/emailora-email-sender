<?php

namespace App\Console\Commands;

use App\Jobs\FinalizeEmailCampaign;
use App\Jobs\PrepareEmailCampaignRecipients;
use App\Jobs\SendEmailCampaignMessages;
use App\Models\EmailCampaign;
use App\Services\Email\CampaignCountRefresher;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('emailora:campaigns:recover')]
#[Description('Recompute campaign counts and resume queued campaign work without double-sending accepted recipients.')]
class RecoverCampaigns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = 0;

        EmailCampaign::whereIn('status', ['queued', 'preparing', 'sending'])->chunkById(100, function ($campaigns) use (&$count): void {
            foreach ($campaigns as $campaign) {
                app(CampaignCountRefresher::class)->refresh($campaign);

                if (! $campaign->recipients()->exists()) {
                    PrepareEmailCampaignRecipients::dispatch($campaign->id, $campaign->recipient_mode ?: 'current_audience')->onQueue('email');
                } elseif ($campaign->recipients()->whereIn('status', ['pending', 'queued'])->exists()) {
                    SendEmailCampaignMessages::dispatch($campaign->id)->onQueue('email');
                } else {
                    FinalizeEmailCampaign::dispatch($campaign->id)->onQueue('email');
                }
                $count++;
            }
        });

        $this->info("Recovered {$count} campaigns.");

        return self::SUCCESS;
    }
}
