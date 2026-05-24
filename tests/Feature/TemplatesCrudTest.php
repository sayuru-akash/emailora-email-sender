<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplatesCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_create_store_sanitizes_html_and_generates_text_fallback(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('templates.store'), [
                'name' => 'Sanitized Template',
                'category' => 'payments',
                'subject' => 'Payment reminder',
                'preheader' => 'Payment due',
                'html_body' => '<p onclick="alert(1)">Hello <script>alert(1)</script><a href="{unsubscribe_url}">Unsubscribe</a></p>',
                'text_body' => '',
                'status' => 'active',
            ])
            ->assertRedirect();

        $template = EmailTemplate::query()->firstOrFail();
        $this->assertStringNotContainsString('<script', $template->html_body);
        $this->assertStringNotContainsString('onclick', $template->html_body);
        $this->assertStringContainsString('Hello', $template->text_body);
        $this->assertSame($user->id, $template->created_by);
    }

    public function test_templates_index_filters_by_search_category_and_status(): void
    {
        EmailTemplate::query()->create([
            'name' => 'CCA Payment',
            'category' => 'payments',
            'subject' => 'Due',
            'html_body' => '<p>Due</p>',
            'status' => 'active',
        ]);
        EmailTemplate::query()->create([
            'name' => 'Newsletter',
            'category' => 'newsletter',
            'subject' => 'News',
            'html_body' => '<p>News</p>',
            'status' => 'inactive',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('templates.index', ['search' => 'CCA', 'category' => 'payments', 'status' => 'active']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Templates/Index')
                ->where('templates.meta.total', 1)
                ->where('templates.data.0.name', 'CCA Payment'));
    }

    public function test_template_duplicate_preview_and_destroy(): void
    {
        $template = EmailTemplate::query()->create([
            'name' => 'Original',
            'subject' => 'Hi {{ name }}',
            'preheader' => 'Preview {{ name }}',
            'html_body' => '<p>Hello {{ name }}</p><p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>',
            'text_body' => 'Hello {{ name }}',
            'status' => 'active',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('templates.duplicate', $template))
            ->assertRedirect();

        $copy = EmailTemplate::query()->where('name', 'Original Copy')->firstOrFail();
        $this->assertSame($template->subject, $copy->subject);

        $this->actingAs($user)
            ->get(route('templates.preview', $template))
            ->assertOk()
            ->assertSee('Hello Sample Contact', false)
            ->assertDontSee('{{ name }}', false);

        $this->actingAs($user)->delete(route('templates.destroy', $template))->assertRedirect(route('templates.index'));
        $this->assertNotNull($template->fresh()->deleted_at);
    }
}
