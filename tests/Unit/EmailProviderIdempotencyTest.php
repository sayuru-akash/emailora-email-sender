<?php

namespace Tests\Unit;

use App\Services\Email\BrevoProvider;
use App\Services\Email\EmailPayload;
use App\Services\Email\ResendProvider;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EmailProviderIdempotencyTest extends TestCase
{
    public function test_resend_provider_sends_idempotency_key_header(): void
    {
        config(['emailora.resend.api_key' => 'resend-key']);
        Http::fake([
            'api.resend.com/*' => Http::response(['id' => 'msg_1']),
        ]);

        app(ResendProvider::class)->send($this->payload());

        Http::assertSent(fn ($request) => $request->hasHeader('Idempotency-Key', 'campaign:1:recipient:2'));
    }

    public function test_brevo_provider_sends_idempotency_key_header(): void
    {
        config(['emailora.brevo.api_key' => 'brevo-key']);
        Http::fake([
            'api.brevo.com/*' => Http::response(['messageId' => 'msg_1']),
        ]);

        app(BrevoProvider::class)->send($this->payload());

        Http::assertSent(fn ($request) => $request->hasHeader('idempotencyKey', 'campaign:1:recipient:2'));
    }

    private function payload(): EmailPayload
    {
        return new EmailPayload(
            to: 'student@example.com',
            subject: 'Subject',
            html: '<p>Hello</p>',
            text: 'Hello',
            fromEmail: 'team@example.com',
            fromName: 'Emailora',
            idempotencyKey: 'campaign:1:recipient:2',
        );
    }
}
