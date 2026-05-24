<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactsTest extends TestCase
{
    use RefreshDatabase;

    public function test_contacts_index_filters_sorts_and_paginates(): void
    {
        $user = User::factory()->create();
        Contact::factory()->create(['email' => 'old@example.com', 'source' => 'import', 'status' => 'inactive']);
        Contact::factory()->create(['email' => 'student@example.com', 'source' => 'manual', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('contacts.index', [
                'search' => 'student',
                'status' => 'active',
                'source' => 'manual',
                'sort' => 'email',
                'direction' => 'asc',
                'per_page' => 10,
            ]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Contacts/Index')
                ->where('contacts.meta.total', 1)
                ->where('contacts.data.0.email', 'student@example.com')
                ->where('filters.search', 'student')
                ->where('filters.status', 'active')
                ->where('filters.source', 'manual'));
    }

    public function test_contact_update_replaces_memberships_and_logs_sync(): void
    {
        $user = User::factory()->create();
        $oldList = ListModel::factory()->create();
        $newList = ListModel::factory()->create();
        $oldTag = Tag::factory()->create();
        $newTag = Tag::factory()->create();
        $contact = Contact::factory()->create();
        $contact->lists()->attach($oldList);
        $contact->tags()->attach($oldTag);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [
                'first_name' => 'Updated',
                'last_name' => 'Contact',
                'full_name' => 'Updated Contact',
                'email' => 'updated@example.com',
                'status' => 'active',
                'consent_status' => 'opted_in',
                'list_ids' => [$newList->id],
                'tag_ids' => [$newTag->id],
            ])
            ->assertRedirect();

        $contact->refresh();
        $this->assertSame('updated@example.com', $contact->email);
        $this->assertTrue($contact->lists()->whereKey($newList->id)->exists());
        $this->assertFalse($contact->lists()->whereKey($oldList->id)->exists());
        $this->assertTrue($contact->tags()->whereKey($newTag->id)->exists());
        $this->assertFalse($contact->tags()->whereKey($oldTag->id)->exists());
        $this->assertDatabaseHas(ActivityLog::class, [
            'event' => 'contact.membership_synced',
            'subject_type' => Contact::class,
            'subject_id' => $contact->id,
        ]);
    }

    public function test_contact_block_unsubscribe_and_bulk_actions_update_statuses(): void
    {
        $user = User::factory()->create();
        $blocked = Contact::factory()->create();
        $unsubscribed = Contact::factory()->create();
        $inactive = Contact::factory()->create();

        $this->actingAs($user)->post(route('contacts.block', $blocked))->assertRedirect();
        $this->actingAs($user)->post(route('contacts.unsubscribe', $unsubscribed))->assertRedirect();
        $this->actingAs($user)->post(route('contacts.bulk-action'), [
            'action' => 'mark_inactive',
            'ids' => [$inactive->id],
        ])->assertRedirect();

        $this->assertSame('blocked', $blocked->refresh()->status);
        $this->assertNotNull($blocked->blocked_at);
        $this->assertSame('unsubscribed', $unsubscribed->refresh()->status);
        $this->assertNotNull($unsubscribed->unsubscribed_at);
        $this->assertSame('inactive', $inactive->refresh()->status);
        $this->assertDatabaseHas(ActivityLog::class, ['event' => 'contact.blocked']);
        $this->assertDatabaseHas(ActivityLog::class, ['event' => 'contact.unsubscribed']);
        $this->assertDatabaseHas(ActivityLog::class, ['event' => 'contact.bulk_action']);
    }

    public function test_contacts_export_streams_csv(): void
    {
        Contact::factory()->create([
            'email' => 'export@example.com',
            'full_name' => '=HYPERLINK("https://example.test")',
            'status' => 'active',
            'source' => '@manual',
        ]);

        $response = $this->actingAs(User::factory()->create())
            ->get(route('contacts.export'))
            ->assertOk()
            ->assertStreamed()
            ->assertHeader('Content-Disposition', 'attachment; filename=contacts.csv');

        $csv = $response->streamedContent();
        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringContainsString("'@manual", $csv);
    }

    public function test_contact_update_allows_same_email_with_different_casing_but_rejects_another_contact_email(): void
    {
        $user = User::factory()->create(['role' => 'owner']);
        $contact = Contact::factory()->create([
            'email' => 'student@example.com',
            'email_normalized' => 'student@example.com',
            'full_name' => 'Student One',
        ]);
        $other = Contact::factory()->create([
            'email' => 'other@example.com',
            'email_normalized' => 'other@example.com',
        ]);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [
                'first_name' => 'Student',
                'last_name' => 'One',
                'full_name' => 'Student One',
                'email' => 'STUDENT@EXAMPLE.COM',
                'status' => 'active',
                'consent_status' => 'opted_in',
                'list_ids' => [],
                'tag_ids' => [],
            ])
            ->assertRedirect();

        $this->assertSame('student@example.com', $contact->refresh()->email_normalized);

        $this->actingAs($user)
            ->put(route('contacts.update', $contact), [
                'first_name' => 'Student',
                'last_name' => 'One',
                'full_name' => 'Student One',
                'email' => $other->email,
                'status' => 'active',
                'consent_status' => 'opted_in',
                'list_ids' => [],
                'tag_ids' => [],
            ])
            ->assertSessionHasErrors('email');
    }
}
