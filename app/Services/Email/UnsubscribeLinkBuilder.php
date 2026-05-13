<?php

namespace App\Services\Email;

use App\Models\CampaignRecipient;
use Illuminate\Support\Facades\URL;

final class UnsubscribeLinkBuilder
{
    public function forRecipient(CampaignRecipient $recipient): string
    {
        return URL::temporarySignedRoute(
            'unsubscribe.show',
            now()->addDays(30),
            ['signedToken' => base64_encode((string) $recipient->id)]
        );
    }
}
