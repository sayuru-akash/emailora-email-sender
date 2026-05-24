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

        $data = [
            'sender' => ['name' => $payload->fromName, 'email' => $payload->fromEmail],
            'to' => [['email' => $payload->to]],
            'subject' => $payload->subject,
        ];

        if ($payload->replyTo) {
            $data['replyTo'] = ['email' => $payload->replyTo];
        }

        if ($payload->html) {
            $data['htmlContent'] = $payload->html;
        }

        if ($payload->text) {
            $data['textContent'] = $payload->text;
        }

        if ($payload->headers !== []) {
            $data['headers'] = (object) $payload->headers;
        }

        if ($payload->tags !== []) {
            $data['tags'] = collect($payload->tags)->map(fn ($value, $name) => "{$name}:{$value}")->values()->all();
        }

        $headers = ['api-key' => $apiKey];
        if ($payload->idempotencyKey) {
            $headers['idempotencyKey'] = $payload->idempotencyKey;
        }

        $response = Http::timeout(config('emailora.timeout'))->withHeaders($headers)->post('https://api.brevo.com/v3/smtp/email', $data);

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
