<?php

namespace App\Services\Email;

use Throwable;

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
        try {
            return $this->provider($provider)->send($payload);
        } catch (Throwable $exception) {
            report($exception);

            return EmailResult::failed(
                'Provider request failed: '.$exception->getMessage(),
                'transport',
                ['exception' => $exception::class],
            );
        }
    }
}
