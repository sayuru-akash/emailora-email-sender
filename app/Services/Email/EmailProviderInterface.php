<?php

namespace App\Services\Email;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

interface EmailProviderInterface
{
    public function send(EmailPayload $payload): EmailResult;

    /** @param Collection<int, EmailPayload> $payloads */
    public function sendBatch(Collection $payloads): Collection;

    public function verifyWebhook(Request $request): bool;

    public function parseWebhook(Request $request): EmailWebhookEvent;
}
