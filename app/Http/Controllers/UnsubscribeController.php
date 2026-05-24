<?php

namespace App\Http\Controllers;

use App\Models\CampaignRecipient;
use App\Models\EmailSuppression;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class UnsubscribeController extends Controller
{
    public function show(string $signedToken): Response
    {
        return Inertia::render('Unsubscribe/Show', ['token' => $signedToken]);
    }

    public function store(string $signedToken): RedirectResponse
    {
        $recipient = CampaignRecipient::findOrFail((int) base64_decode($signedToken));
        $recipient->update(['status' => 'skipped', 'skip_reason' => 'unsubscribed']);
        $recipient->contact?->update(['status' => 'unsubscribed', 'unsubscribed_at' => now()]);

        EmailSuppression::updateOrCreate(
            ['email_normalized' => $recipient->email_normalized],
            ['reason' => 'unsubscribed', 'email_campaign_id' => $recipient->email_campaign_id, 'metadata' => ['source' => 'one_click']]
        );

        return redirect(URL::signedRoute('unsubscribe.show', ['signedToken' => $signedToken]))
            ->with('success', 'Unsubscribe recorded.');
    }
}
