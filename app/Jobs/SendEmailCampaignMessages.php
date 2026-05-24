<?php

namespace App\Jobs;

use App\Models\EmailCampaign;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendEmailCampaignMessages implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public int $uniqueFor = 300;

    public function __construct(public int $campaignId) {}

    public function uniqueId(): string
    {
        return (string) $this->campaignId;
    }

    public function handle(): void
    {
        $campaign = EmailCampaign::findOrFail($this->campaignId);
        if (in_array($campaign->status, ['cancelled', 'paused', 'completed'], true)) {
            return;
        }

        $campaign->update(['status' => 'sending', 'started_at' => $campaign->started_at ?: now()]);

        $staleSendingBefore = now()->subMinutes(5);

        $campaign->recipients()
            ->where(function ($query) use ($staleSendingBefore): void {
                $query
                    ->whereIn('status', ['pending', 'queued'])
                    ->orWhere(function ($query) use ($staleSendingBefore): void {
                        $query->where('status', 'sending')->where('last_attempt_at', '<', $staleSendingBefore);
                    });
            })
            ->chunkById(config('emailora.chunk_size'), function ($recipients): void {
                foreach ($recipients as $recipient) {
                    $recipient->update(['status' => 'queued', 'queued_at' => now()]);
                    SendSingleEmail::dispatch($recipient->id)->onQueue('email');
                }
            });

        FinalizeEmailCampaign::dispatch($campaign->id)->delay(now()->addSeconds(10))->onQueue('email');
    }
}
