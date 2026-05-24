<?php

namespace App\Services\Email;

use App\Models\EmailCampaign;

final class CampaignCountRefresher
{
    public function refresh(EmailCampaign $campaign): EmailCampaign
    {
        $counts = $campaign->recipients()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $campaign->forceFill([
            'total_recipients' => $counts->sum(),
            'pending_count' => (int) ($counts['pending'] ?? 0),
            'queued_count' => (int) (($counts['queued'] ?? 0) + ($counts['sending'] ?? 0)),
            'sent_count' => (int) ($counts['sent'] ?? 0),
            'delivered_count' => (int) ($counts['delivered'] ?? 0),
            'opened_count' => (int) ($counts['opened'] ?? 0),
            'clicked_count' => (int) ($counts['clicked'] ?? 0),
            'failed_count' => (int) ($counts['failed'] ?? 0),
            'bounced_count' => (int) ($counts['bounced'] ?? 0),
            'complained_count' => (int) ($counts['complained'] ?? 0),
            'skipped_count' => (int) ($counts['skipped'] ?? 0),
        ])->save();

        return $campaign->refresh();
    }
}
