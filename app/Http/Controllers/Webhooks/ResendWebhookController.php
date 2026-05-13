<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailWebhookEvent;
use App\Services\Email\ResendProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResendWebhookController extends Controller
{
    public function __invoke(Request $request, ResendProvider $provider): JsonResponse
    {
        abort_unless($provider->verifyWebhook($request), 401);
        $event = $provider->parseWebhook($request);
        ProcessEmailWebhookEvent::dispatch($event)->onQueue('email');

        return response()->json(['ok' => true]);
    }
}
