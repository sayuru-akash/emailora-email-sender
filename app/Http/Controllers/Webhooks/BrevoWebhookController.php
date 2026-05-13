<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessEmailWebhookEvent;
use App\Services\Email\BrevoProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrevoWebhookController extends Controller
{
    public function __invoke(Request $request, BrevoProvider $provider): JsonResponse
    {
        abort_unless($provider->verifyWebhook($request), 401);
        $event = $provider->parseWebhook($request);
        ProcessEmailWebhookEvent::dispatch($event)->onQueue('email');

        return response()->json(['ok' => true]);
    }
}
