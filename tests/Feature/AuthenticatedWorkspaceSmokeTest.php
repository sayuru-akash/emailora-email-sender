<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\ContactImport;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\ListModel;
use App\Models\SavedSegment;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedWorkspaceSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Contact $contact;

    private ListModel $list;

    private Tag $tag;

    private EmailTemplate $template;

    private EmailCampaign $campaign;

    private ContactImport $import;

    private SavedSegment $segment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['role' => 'owner']);
        $this->contact = Contact::factory()->create(['full_name' => 'Smoke Contact', 'email' => 'smoke@example.com']);
        $this->list = ListModel::factory()->create(['name' => 'Smoke List']);
        $this->tag = Tag::factory()->create(['name' => 'Smoke Tag']);
        $this->template = EmailTemplate::query()->create([
            'name' => 'Smoke Template',
            'subject' => 'Smoke subject',
            'html_body' => '<p>Hello {{ name }}</p><p><a href="{{ unsubscribe_url }}">Unsubscribe</a></p>',
            'text_body' => 'Hello {{ name }}',
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);
        $this->campaign = EmailCampaign::factory()->create([
            'name' => 'Smoke Campaign',
            'email_template_id' => $this->template->id,
            'target_type' => 'manual_selection',
            'target_filters' => ['contact_ids' => [$this->contact->id]],
            'created_by' => $this->owner->id,
        ]);
        $this->import = ContactImport::factory()->create([
            'file_name' => 'smoke.csv',
            'uploaded_by' => $this->owner->id,
            'preview_rows' => [['email' => 'smoke@example.com']],
        ]);
        $this->segment = SavedSegment::query()->create([
            'name' => 'Smoke Segment',
            'filters' => ['status' => 'active', 'source' => null, 'tags' => [], 'lists' => []],
            'status' => 'active',
            'created_by' => $this->owner->id,
        ]);

        ActivityLog::factory()->create([
            'user_id' => $this->owner->id,
            'user_email' => $this->owner->email,
            'event' => 'smoke.created',
        ]);
    }

    public function test_authenticated_workspace_pages_render_without_server_errors(): void
    {
        $routes = [
            route('dashboard') => 'Dashboard',
            route('contacts.index') => 'Contacts/Index',
            route('contacts.create') => 'Contacts/Form',
            route('contacts.show', $this->contact) => 'Contacts/Show',
            route('contacts.edit', $this->contact) => 'Contacts/Form',
            route('lists.index') => 'Lists/Index',
            route('lists.create') => 'Lists/Form',
            route('lists.show', $this->list) => 'Lists/Show',
            route('lists.edit', $this->list) => 'Lists/Form',
            route('tags.index') => 'Tags/Index',
            route('tags.create') => 'Tags/Form',
            route('tags.show', $this->tag) => 'Tags/Show',
            route('tags.edit', $this->tag) => 'Tags/Form',
            route('templates.index') => 'Templates/Index',
            route('templates.create') => 'Templates/Form',
            route('templates.show', $this->template) => 'Templates/Show',
            route('templates.edit', $this->template) => 'Templates/Form',
            route('campaigns.index') => 'Campaigns/Index',
            route('campaigns.create') => 'Campaigns/Builder',
            route('campaigns.builder') => 'Campaigns/Builder',
            route('campaigns.show', $this->campaign) => 'Campaigns/Show',
            route('campaigns.edit', $this->campaign) => 'Campaigns/Builder',
            route('campaigns.recipients', $this->campaign) => 'Campaigns/Recipients',
            route('campaigns.report', $this->campaign) => 'Campaigns/Report',
            route('imports.index') => 'Imports/Index',
            route('imports.create') => 'Imports/Create',
            route('imports.show', $this->import) => 'Imports/Show',
            route('imports.mapping', $this->import) => 'Imports/Mapping',
            route('reports.index') => 'Reports/Index',
            route('reports.campaign', $this->campaign) => 'Campaigns/Report',
            route('segments.index') => 'Segments/Index',
            route('segments.create') => 'Segments/Form',
            route('segments.show', $this->segment) => 'Segments/Show',
            route('segments.edit', $this->segment) => 'Segments/Form',
            route('settings.index') => 'settings/Index',
            route('profile.edit') => 'settings/Profile',
            route('profile.show') => 'settings/Profile',
            route('security.edit') => 'settings/Security',
            route('appearance.edit') => 'settings/Appearance',
            route('users.index') => 'Users/Index',
            route('users.create') => 'Users/Form',
            route('users.show', $this->owner) => 'Users/Show',
            route('users.edit', $this->owner) => 'Users/Form',
            route('activity-logs.index') => 'ActivityLogs/Index',
        ];

        foreach ($routes as $url => $component) {
            $this->actingAs($this->owner)
                ->withSession(['auth.password_confirmed_at' => time()])
                ->get($url)
                ->assertOk()
                ->assertInertia(fn ($page) => $page->component($component));
        }
    }

    public function test_workspace_json_search_and_audience_endpoints_return_expected_shape(): void
    {
        $this->actingAs($this->owner)
            ->getJson(route('global-search', ['query' => 'Smoke']))
            ->assertOk()
            ->assertJsonPath('query', 'Smoke')
            ->assertJsonStructure(['groups' => [['label', 'items']]]);

        $this->actingAs($this->owner)
            ->getJson(route('campaigns.audience.contacts', ['search' => 'smoke']))
            ->assertOk()
            ->assertJsonStructure(['contacts' => [['id', 'name', 'email', 'status', 'company']]]);

        $this->actingAs($this->owner)
            ->postJson(route('campaigns.audience.estimate'), [
                'target_type' => 'manual_selection',
                'target_filters' => ['contact_ids' => [$this->contact->id]],
            ])
            ->assertOk()
            ->assertJsonStructure(['count', 'suppressed_count', 'sendable_count'])
            ->assertJsonPath('sendable_count', 1);
    }
}
