<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactListTagCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_syncs_lists_and_tags(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->post(route('contacts.store'), [
            'first_name' => 'Sayuru',
            'last_name' => 'Amarasinghe',
            'full_name' => 'Sayuru Amarasinghe',
            'email' => 'Sayuru555@Gmail.com',
            'phone' => '0710000000',
            'company' => 'Codezela',
            'job_title' => 'Director',
            'country' => 'Sri Lanka',
            'district' => 'Colombo',
            'city' => 'Colombo',
            'source' => 'manual',
            'status' => 'active',
            'consent_status' => 'unknown',
            'notes' => 'Test contact',
            'list_ids' => [$list->id],
            'tag_ids' => [$tag->id],
        ]);

        $contact = Contact::query()->firstOrFail();
        $response->assertRedirect(route('contacts.show', $contact));
        $this->assertSame('sayuru555@gmail.com', $contact->email);
        $this->assertTrue($contact->lists()->whereKey($list->id)->exists());
        $this->assertTrue($contact->tags()->whereKey($tag->id)->exists());
    }

    public function test_contact_duplicate_email_is_validated_case_insensitively(): void
    {
        Contact::factory()->create([
            'email' => 'student@example.com',
            'email_normalized' => 'student@example.com',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('contacts.store'), [
                'email' => 'Student@Example.com',
                'status' => 'active',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_list_create_edit_and_duplicate_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('lists.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Lists/Form'));

        $response = $this->actingAs($user)->post(route('lists.store'), [
            'name' => 'CCA',
            'description' => 'CCA contacts',
            'status' => 'active',
            'color' => '#4f46e5',
        ]);

        $list = ListModel::query()->firstOrFail();
        $response->assertRedirect(route('lists.show', $list));

        $this->actingAs($user)
            ->post(route('lists.store'), [
                'name' => 'CCA',
                'description' => null,
                'status' => 'active',
                'color' => '#4f46e5',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->get(route('lists.edit', $list))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Lists/Form'));

        $this->actingAs($user)
            ->put(route('lists.update', $list), [
                'name' => 'CCA Updated',
                'description' => 'Updated contacts',
                'status' => 'inactive',
                'color' => '#0f766e',
            ])
            ->assertRedirect(route('lists.show', $list));

        $this->assertSame('cca-updated', $list->refresh()->slug);
    }

    public function test_list_membership_can_be_managed_from_list_actions(): void
    {
        $user = User::factory()->create();
        $list = ListModel::factory()->create();
        $first = Contact::factory()->create();
        $second = Contact::factory()->create();

        $this->actingAs($user)
            ->post(route('lists.add-contacts', $list), [
                'contact_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Contacts added.');

        $this->assertTrue($list->contacts()->whereKey($first->id)->exists());
        $this->assertTrue($list->contacts()->whereKey($second->id)->exists());

        $this->actingAs($user)
            ->post(route('lists.remove-contacts', $list), [
                'contact_ids' => [$first->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Contacts removed.');

        $this->assertFalse($list->contacts()->whereKey($first->id)->exists());
        $this->assertTrue($list->contacts()->whereKey($second->id)->exists());
    }

    public function test_tag_create_edit_and_duplicate_validation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tags.create'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tags/Form'));

        $response = $this->actingAs($user)->post(route('tags.store'), [
            'name' => 'Paid',
            'description' => 'Paid students',
            'color' => '#4f46e5',
        ]);

        $tag = Tag::query()->firstOrFail();
        $response->assertRedirect(route('tags.show', $tag));

        $this->actingAs($user)
            ->post(route('tags.store'), [
                'name' => 'Paid',
                'description' => null,
                'color' => '#4f46e5',
            ])
            ->assertSessionHasErrors('name');

        $this->actingAs($user)
            ->put(route('tags.update', $tag), [
                'name' => 'Paid Updated',
                'description' => 'Updated tag',
                'color' => '#0f766e',
            ])
            ->assertRedirect(route('tags.show', $tag));

        $this->assertSame('paid-updated', $tag->refresh()->slug);
    }
}
