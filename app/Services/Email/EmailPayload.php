<?php

namespace App\Services\Email;

final readonly class EmailPayload
{
    public function __construct(
        public string $to,
        public string $subject,
        public ?string $html,
        public ?string $text,
        public string $fromEmail,
        public string $fromName,
        public ?string $replyTo = null,
        public array $headers = [],
        public array $tags = [],
        public ?string $idempotencyKey = null,
    ) {}
}
