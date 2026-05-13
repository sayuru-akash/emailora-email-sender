<?php

namespace App\Services\Email;

final class EmailService
{
    public function provider(?string $provider = null): EmailProviderInterface
    {
        return match ($provider ?: config('emailora.provider')) {
            'brevo' => app(BrevoProvider::class),
            default => app(ResendProvider::class),
        };
    }

    public function send(EmailPayload $payload, ?string $provider = null): EmailResult
    {
        return $this->provider($provider)->send($payload);
    }
}
