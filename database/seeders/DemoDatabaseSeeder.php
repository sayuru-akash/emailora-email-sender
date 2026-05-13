<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::query()->firstOrCreate(
            ['email' => 'demo-owner@example.com'],
            ['name' => 'Demo Owner', 'password' => Hash::make('password'), 'role' => 'owner', 'status' => 'active']
        );

        $newsletter = ListModel::query()->create(['name' => 'Newsletter', 'slug' => 'newsletter', 'created_by' => $owner->id]);
        $vip = Tag::query()->create(['name' => 'VIP', 'slug' => 'vip', 'created_by' => $owner->id]);

        Contact::factory()->count(12)->create(['created_by' => $owner->id])->each(function (Contact $contact) use ($newsletter, $vip): void {
            $contact->lists()->syncWithoutDetaching([$newsletter->id]);
            $contact->tags()->syncWithoutDetaching([$vip->id]);
        });

        EmailTemplate::query()->create([
            'name' => 'Welcome newsletter',
            'category' => 'newsletter',
            'subject' => 'Welcome to {company}',
            'preheader' => 'Latest updates for {first_name}',
            'html_body' => '<p>Hello {first_name},</p><p>Here are the latest updates.</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>',
            'text_body' => "Hello {first_name},\n\nHere are the latest updates.\n\nUnsubscribe: {unsubscribe_url}",
            'variables' => ['first_name', 'company', 'unsubscribe_url'],
            'created_by' => $owner->id,
        ]);

        EmailCampaign::query()->create([
            'name' => 'Demo newsletter draft',
            'subject' => 'May product update',
            'from_name' => 'Emailora',
            'from_email' => 'no-reply@example.com',
            'html_body' => '<p>Demo campaign body.</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>',
            'text_body' => "Demo campaign body.\nUnsubscribe: {unsubscribe_url}",
            'status' => 'draft',
            'created_by' => $owner->id,
        ]);
    }
}
