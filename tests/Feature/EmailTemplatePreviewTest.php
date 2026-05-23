<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_preview_requires_authentication(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Preview Template',
            'subject' => 'Preview subject',
            'html_body' => '<p>Hello</p>',
        ]);

        $this->get(route('templates.preview', $template))
            ->assertRedirect(route('login'));
    }

    public function test_template_preview_renders_email_html_with_image_friendly_headers(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::query()->create([
            'name' => 'Preview Template',
            'subject' => 'Preview subject',
            'html_body' => '<html><head><title>Email</title></head><body><img src="https://example.com/logo.png" alt="Logo"></body></html>',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('templates.preview', $template));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/html; charset=UTF-8');
        $response->assertSee('<base target="_blank">', false);
        $response->assertSee('https://example.com/logo.png', false);

        $contentSecurityPolicy = $response->headers->get('Content-Security-Policy', '');
        $this->assertStringContainsString('img-src * data: blob:', $contentSecurityPolicy);
        $this->assertStringContainsString("script-src 'none'", $contentSecurityPolicy);
        $this->assertStringContainsString("frame-ancestors 'self'", $contentSecurityPolicy);
    }

    public function test_template_preview_wraps_body_fragments_in_a_document(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::query()->create([
            'name' => 'Fragment Template',
            'subject' => 'Fragment subject',
            'html_body' => '<table><tr><td><img src="https://example.com/banner.png" alt=""></td></tr></table>',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('templates.preview', $template));

        $response->assertOk();
        $response->assertSee('<!doctype html>', false);
        $response->assertSee('<base target="_blank">', false);
        $response->assertSee('https://example.com/banner.png', false);
    }
}
