<?php

namespace App\Services\Email;

final readonly class EmailResult
{
    public function __construct(
        public bool $accepted,
        public ?string $providerMessageId = null,
        public array $response = [],
        public ?string $errorMessage = null,
        public string $failureType = 'none',
    ) {}

    public static function accepted(string $providerMessageId, array $response = []): self
    {
        return new self(true, $providerMessageId, $response);
    }

    public static function failed(string $message, string $failureType = 'provider_rejected', array $response = []): self
    {
        return new self(false, null, $response, $message, $failureType);
    }
}
