<?php

namespace Tests\Feature\Webhooks;

use App\Jobs\ProcessEmailWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_resend_webhook_rejects_invalid_signature_without_dispatching_job(): void
    {
        config(['emailora.resend.webhook_secret' => 'resend-secret']);
        Queue::fake();

        $this->postJson(route('webhooks.email.resend'), ['type' => 'email.delivered'])
            ->assertUnauthorized();

        Queue::assertNotPushed(ProcessEmailWebhookEvent::class);
    }

    public function test_resend_webhook_accepts_valid_signature_and_dispatches_email_event(): void
    {
        config(['emailora.resend.webhook_secret' => 'resend-secret']);
        Queue::fake();
        $payload = [
            'id' => 'evt_resend_1',
            'type' => 'email.delivered',
            'data' => [
                'email_id' => 'msg_resend_1',
                'to' => ['student@example.com'],
            ],
        ];
        $content = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->withHeader('svix-signature', hash_hmac('sha256', $content, 'resend-secret'))
            ->postJson(route('webhooks.email.resend'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        Queue::assertPushed(ProcessEmailWebhookEvent::class, fn (ProcessEmailWebhookEvent $job) => $job->event->provider === 'resend'
            && $job->event->eventType === 'delivered'
            && $job->event->providerEventId === 'evt_resend_1'
            && $job->event->providerMessageId === 'msg_resend_1');
    }

    public function test_brevo_webhook_accepts_valid_signature_and_dispatches_email_event(): void
    {
        config(['emailora.brevo.webhook_secret' => 'brevo-secret']);
        Queue::fake();
        $payload = [
            'eventId' => 'evt_brevo_1',
            'event' => 'delivered',
            'message-id' => 'msg_brevo_1',
            'email' => 'student@example.com',
        ];
        $content = json_encode($payload, JSON_THROW_ON_ERROR);

        $this->withHeader('x-brevo-signature', hash_hmac('sha256', $content, 'brevo-secret'))
            ->postJson(route('webhooks.email.brevo'), $payload)
            ->assertOk()
            ->assertJson(['ok' => true]);

        Queue::assertPushed(ProcessEmailWebhookEvent::class, fn (ProcessEmailWebhookEvent $job) => $job->event->provider === 'brevo'
            && $job->event->eventType === 'delivered'
            && $job->event->providerEventId === 'evt_brevo_1'
            && $job->event->providerMessageId === 'msg_brevo_1');
    }
}
