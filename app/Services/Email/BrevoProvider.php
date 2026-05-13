<?php

namespace App\Services\Email;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class BrevoProvider implements EmailProviderInterface
{
    public function send(EmailPayload $payload): EmailResult
    {
        $apiKey = config('emailora.brevo.api_key');
        if (! $apiKey) {
            return EmailResult::failed('Brevo API key is not configured.', 'configuration');
        }

        $response = Http::timeout(config('emailora.timeout'))->withHeaders(['api-key' => $apiKey])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => ['name' => $payload->fromName, 'email' => $payload->fromEmail],
            'to' => [['email' => $payload->to]],
            'replyTo' => $payload->replyTo ? ['email' => $payload->replyTo] : null,
            'subject' => $payload->subject,
            'htmlContent' => $payload->html,
            'textContent' => $payload->text,
            'headers' => $payload->headers,
            'tags' => collect($payload->tags)->map(fn ($value, $name) => "{$name}:{$value}")->values()->all(),
        ]);

        if (! $response->successful()) {
            return EmailResult::failed('Brevo rejected the email.', $response->status() === 429 ? 'rate_limited' : 'provider_rejected', $response->json() ?? []);
        }

        return EmailResult::accepted((string) ($response->json('messageId') ?: Str::uuid()), $response->json() ?? []);
    }

    public function sendBatch(Collection $payloads): Collection
    {
        return $payloads->map(fn (EmailPayload $payload) => $this->send($payload));
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = config('emailora.brevo.webhook_secret');
        $signature = (string) $request->header('x-brevo-signature', '');

        return $secret && hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature);
    }

    public function parseWebhook(Request $request): EmailWebhookEvent
    {
        $payload = $request->json()->all();

        return new EmailWebhookEvent(
            'brevo',
            (string) ($payload['event'] ?? 'unknown'),
            isset($payload['eventId']) ? (string) $payload['eventId'] : null,
            $payload['message-id'] ?? $payload['messageId'] ?? null,
            $payload['email'] ?? null,
            $payload,
            now(),
            []
        );
    }
}
