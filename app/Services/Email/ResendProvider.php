<?php

namespace App\Services\Email;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class ResendProvider implements EmailProviderInterface
{
    public function send(EmailPayload $payload): EmailResult
    {
        $apiKey = config('emailora.resend.api_key');
        if (! $apiKey) {
            return EmailResult::failed('Resend API key is not configured.', 'configuration');
        }

        $request = Http::timeout(config('emailora.timeout'))->withToken($apiKey);
        if ($payload->idempotencyKey) {
            $request = $request->withHeaders(['Idempotency-Key' => $payload->idempotencyKey]);
        }

        $response = $request->post('https://api.resend.com/emails', [
            'from' => "{$payload->fromName} <{$payload->fromEmail}>",
            'to' => [$payload->to],
            'reply_to' => $payload->replyTo,
            'subject' => $payload->subject,
            'html' => $payload->html,
            'text' => $payload->text,
            'headers' => $payload->headers,
            'tags' => collect($payload->tags)->map(fn ($value, $name) => ['name' => (string) $name, 'value' => (string) $value])->values()->all(),
        ]);

        if (! $response->successful()) {
            return EmailResult::failed('Resend rejected the email.', $response->status() === 429 ? 'rate_limited' : 'provider_rejected', $response->json() ?? []);
        }

        return EmailResult::accepted((string) ($response->json('id') ?: Str::uuid()), $response->json() ?? []);
    }

    public function sendBatch(Collection $payloads): Collection
    {
        return $payloads->map(fn (EmailPayload $payload) => $this->send($payload));
    }

    public function verifyWebhook(Request $request): bool
    {
        $secret = config('emailora.resend.webhook_secret');
        if (! $secret) {
            return false;
        }

        $signature = (string) $request->header('svix-signature', $request->header('resend-signature', ''));

        return hash_equals(hash_hmac('sha256', $request->getContent(), $secret), $signature);
    }

    public function parseWebhook(Request $request): EmailWebhookEvent
    {
        $payload = $request->json()->all();
        $data = $payload['data'] ?? [];

        return new EmailWebhookEvent(
            'resend',
            str_replace('email.', '', (string) ($payload['type'] ?? $payload['event'] ?? 'unknown')),
            isset($payload['id']) ? (string) $payload['id'] : null,
            $data['email_id'] ?? $data['id'] ?? null,
            $data['to'][0] ?? $data['email'] ?? null,
            $payload,
            now(),
            collect($data['tags'] ?? [])->pluck('value', 'name')->all(),
        );
    }
}
