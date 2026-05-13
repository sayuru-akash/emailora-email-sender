<?php

namespace App\Services\Email;

use Illuminate\Support\Carbon;

final readonly class EmailWebhookEvent
{
    public function __construct(
        public string $provider,
        public string $eventType,
        public ?string $providerEventId,
        public ?string $providerMessageId,
        public ?string $email,
        public array $payload,
        public ?Carbon $occurredAt = null,
        public array $tags = [],
    ) {}
}
