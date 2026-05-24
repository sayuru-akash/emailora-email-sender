<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListTagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_index_filters_and_show_excludes_existing_members_from_available_contacts(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->create(['name' => 'CCA List', 'status' => 'active']);
        ListModel::factory()->create(['name' => 'Inactive List', 'status' => 'inactive']);
        $member = Contact::factory()->create(['email' => 'member@example.com']);
        $available = Contact::factory()->create(['email' => 'available@example.com']);
        $list->contacts()->attach($member);

        $this->actingAs($user)
            ->get(route('lists.index', ['search' => 'CCA', 'status' => 'active']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Lists/Index')
                ->where('lists.meta.total', 1)
                ->where('lists.data.0.name', 'CCA List'));

        $this->actingAs($user)
            ->get(route('lists.show', $list))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Lists/Show')
                ->where('contacts.meta.total', 1)
                ->where('contacts.data.0.email', 'member@example.com')
                ->where('availableContacts.0.email', $available->email));
    }

    public function test_list_export_and_destroy_do_not_delete_contacts(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->create(['name' => 'Export List']);
        $contact = Contact::factory()->create(['email' => 'list-export@example.com']);
        $list->contacts()->attach($contact);

        $this->actingAs($user)
            ->get(route('lists.export', $list))
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Disposition', 'attachment; filename=export-list-contacts.csv');

        $this->actingAs($user)->delete(route('lists.destroy', $list))->assertRedirect();

        $this->assertDatabaseMissing('lists', ['id' => $list->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_tags_index_show_and_destroy_preserve_contacts(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'Paid']);
        $contact = Contact::factory()->create(['email' => 'tagged@example.com']);
        $tag->contacts()->attach($contact);

        $this->actingAs($user)
            ->get(route('tags.index', ['search' => 'Paid']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tags/Index')
                ->where('tags.meta.total', 1)
                ->where('tags.data.0.name', 'Paid'));

        $this->actingAs($user)
            ->get(route('tags.show', $tag))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Tags/Show')
                ->where('contacts.meta.total', 1)
                ->where('contacts.data.0.email', 'tagged@example.com'));

        $this->actingAs($user)->delete(route('tags.destroy', $tag))->assertRedirect();

        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }
}
