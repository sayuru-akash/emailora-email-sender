<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CampaignBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_includes_templates_lists_tags_and_selected_contacts(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::query()->create([
            'name' => 'CCA Reminder',
            'subject' => 'Payment due',
            'html_body' => '<p>Hello</p>',
        ]);
        $list = ListModel::query()->create(['name' => 'CCA']);
        $tag = Tag::query()->create(['name' => 'Paid']);
        $contact = Contact::query()->create([
            'full_name' => 'Student One',
            'email' => 'student@example.com',
            'status' => 'active',
        ]);
        $list->contacts()->attach($contact);
        $tag->contacts()->attach($contact);
        $campaign = EmailCampaign::factory()->create([
            'email_template_id' => $template->id,
            'target_type' => 'manual_selection',
            'target_filters' => ['contact_ids' => [$contact->id]],
        ]);

        $this->actingAs($user)
            ->get(route('campaigns.edit', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Campaigns/Builder')
                ->has('templates', 1)
                ->where('templates.0.id', $template->id)
                ->where('lists.0.contacts_count', 1)
                ->where('tags.0.contacts_count', 1)
                ->where('selectedContacts.0.email', 'student@example.com'));
    }

    public function test_campaign_can_be_saved_from_a_template_with_a_list_audience(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::query()->create([
            'name' => 'CCA Reminder',
            'subject' => 'Payment due',
            'html_body' => '<p>Hello <a href="{unsubscribe_url}">Unsubscribe</a></p>',
        ]);
        $list = ListModel::query()->create(['name' => 'CCA']);

        $response = $this->actingAs($user)->post(route('campaigns.store'), [
            'name' => 'CCA due campaign',
            'provider' => 'brevo',
            'from_name' => 'Codezela Technologies',
            'from_email' => 'team@codezela.com',
            'reply_to_email' => null,
            'subject' => $template->subject,
            'preheader' => null,
            'html_body' => $template->html_body,
            'text_body' => null,
            'email_template_id' => $template->id,
            'target_type' => 'list',
            'target_filters' => ['list_ids' => [$list->id]],
            'status' => 'draft',
        ]);

        $campaign = EmailCampaign::query()->firstOrFail();
        $response->assertRedirect(route('campaigns.show', $campaign));
        $this->assertSame($template->id, $campaign->email_template_id);
        $this->assertSame('list', $campaign->target_type);
        $this->assertSame([$list->id], $campaign->target_filters['list_ids']);
    }

    public function test_campaign_preview_requires_authentication_and_renders_html(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'html_body' => '<html><head></head><body><img src="https://example.com/logo.png" alt=""></body></html>',
        ]);

        $this->get(route('campaigns.preview', $campaign))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.preview', $campaign))
            ->assertOk()
            ->assertSee('<base target="_blank">', false)
            ->assertSee('https://example.com/logo.png', false)
            ->assertHeader('Content-Type', 'text/html; charset=UTF-8');
    }

    public function test_campaign_preview_renders_sample_variables_and_preheader(): void
    {
        $campaign = EmailCampaign::factory()->create([
            'subject' => 'Hi {{ name }}',
            'preheader' => 'Preview for {{ name }}',
            'html_body' => '<p>Hello {{ name }}</p><p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.preview', $campaign))
            ->assertOk()
            ->assertSee('Hello Sample Contact', false)
            ->assertSee('Preview for Sample Contact', false)
            ->assertSee('https://example.com/unsubscribe/sample', false)
            ->assertDontSee('{{ name }}', false);
    }

    public function test_draft_recipients_page_shows_target_audience_before_preparation(): void
    {
        $contact = Contact::query()->create([
            'full_name' => 'Target Student',
            'email' => 'target@example.com',
            'status' => 'active',
        ]);
        $campaign = EmailCampaign::factory()->create([
            'status' => 'draft',
            'target_type' => 'manual_selection',
            'target_filters' => ['contact_ids' => [$contact->id]],
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('campaigns.recipients', $campaign))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('mode', 'target_audience')
                ->where('recipients.meta.total', 1)
                ->where('recipients.data.0.email_normalized', 'target@example.com')
                ->where('recipients.data.0.status', 'targeted'));
    }
}
