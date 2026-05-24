<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailWebhookEvent;
use App\Services\Activity\ActivityLogger;
use App\Services\Email\ResendProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendWebhookController extends Controller
{
    public function __invoke(Request $request, ResendProvider $provider, ActivityLogger $activity): JsonResponse
    {
        if (! $provider->verifyWebhook($request)) {
            $activity->log('webhook.rejected', 'Resend webhook signature was rejected.', null, [
                'provider' => 'resend',
                'ip' => $request->ip(),
            ], 'webhooks', 'warning');
            abort(401);
        }

        $event = $provider->parseWebhook($request);
        ProcessEmailWebhookEvent::dispatch($event)->onQueue('email');
        $activity->log('webhook.accepted', 'Resend webhook event accepted.', null, [
            'provider' => 'resend',
            'event_type' => $event->eventType,
            'message_id' => $event->providerMessageId,
        ], 'webhooks');

        return response()->json(['ok' => true]);
    }
}
