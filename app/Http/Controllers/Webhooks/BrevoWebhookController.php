<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailWebhookEvent;
use App\Services\Activity\ActivityLogger;
use App\Services\Email\BrevoProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, BrevoProvider $provider, ActivityLogger $activity): JsonResponse
    {
        if (! $provider->verifyWebhook($request)) {
            $activity->log('webhook.rejected', 'Brevo webhook signature was rejected.', null, [
                'provider' => 'brevo',
                'ip' => $request->ip(),
            ], 'webhooks', 'warning');
            abort(401);
        }

        $event = $provider->parseWebhook($request);
        ProcessEmailWebhookEvent::dispatch($event)->onQueue('email');
        $activity->log('webhook.accepted', 'Brevo webhook event accepted.', null, [
            'provider' => 'brevo',
            'event_type' => $event->eventType,
            'message_id' => $event->providerMessageId,
        ], 'webhooks');

        return response()->json(['ok' => true]);
    }
}
