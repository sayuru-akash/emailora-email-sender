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

    public function dedupeKey(): string
    {
        return $this->providerEventId ?: hash('sha256', implode('|', [
            $this->provider,
            $this->eventType,
            $this->providerMessageId ?? '',
            $this->email ?? '',
        ]));
    }

    public function sanitizedPayload(): array
    {
        return [
            'provider' => $this->provider,
            'event_type' => $this->eventType,
            'tags' => $this->tags,
        ];
    }
}
