<?php

namespace App\Services\Email;

use Carbon\CarbonInterface;

final readonly class EmailWebhookEvent
{
    public function __construct(
        public string $provider,
        public string $eventType,
        public ?string $providerEventId,
        public ?string $providerMessageId,
        public ?string $email,
        public array $payload,
        public ?CarbonInterface $occurredAt = null,
        public array $tags = [],
    ) {}
}
