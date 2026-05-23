<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class EmailTemplateFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_edit_page_renders_existing_template(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::query()->create([
            'name' => 'CCB Reminder',
            'category' => 'newsletter',
            'subject' => 'Payment due',
            'preheader' => 'Please complete your payment.',
            'html_body' => '<p>Hello</p>',
            'text_body' => 'Hello',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('templates.edit', $template))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Form')
                ->where('template.id', $template->id)
                ->where('template.name', 'CCB Reminder')
                ->where('template.subject', 'Payment due'));
    }

    public function test_template_update_saves_changes(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::query()->create([
            'name' => 'Old Reminder',
            'subject' => 'Old subject',
            'html_body' => '<p>Old</p>',
            'status' => 'active',
            'created_by' => $user->id,
        ]);

        $this->actingAs($user)
            ->put(route('templates.update', $template), [
                'name' => 'Updated Reminder',
                'category' => 'payments',
                'subject' => 'Updated subject',
                'preheader' => 'Updated preheader',
                'html_body' => '<p>Updated <a href="{unsubscribe_url}">Unsubscribe</a></p>',
                'text_body' => '',
                'status' => 'inactive',
            ])
            ->assertRedirect();

        $template->refresh();
        $this->assertSame('Updated Reminder', $template->name);
        $this->assertSame('payments', $template->category);
        $this->assertSame('Updated subject', $template->subject);
        $this->assertSame('inactive', $template->status);
        $this->assertStringContainsString('Updated', $template->html_body);
    }
}
