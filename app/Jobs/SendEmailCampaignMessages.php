<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailCampaignMessages implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $campaignId) {}

    public function handle(): void
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);
        if (in_array($campaign->status, ['cancelled', 'paused', 'completed'], true)) {
            return;
        }

        $campaign->update(['status' => 'sending', 'started_at' => $campaign->started_at ?: now()]);

        $campaign->recipients()->whereIn('status', ['pending', 'queued'])->chunkById(config('emailora.chunk_size'), function ($recipients): void {
            foreach ($recipients as $recipient) {
                $recipient->update(['status' => 'queued', 'queued_at' => now()]);
                SendSingleEmail::dispatch($recipient->id)->onQueue('email');
            }
        });

        FinalizeEmailCampaign::dispatch($campaign->id)->delay(now()->addSeconds(10))->onQueue('email');
    }
}
