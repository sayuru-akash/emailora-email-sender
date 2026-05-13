<?php

namespace Database\Factories;

use App\Models\EmailCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmailCampaign>
 */
class EmailCampaignFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->sentence(3),
            'subject' => fake()->sentence(6),
            'from_name' => 'Emailora',
            'from_email' => 'no-reply@example.com',
            'html_body' => '<p>Hello {first_name}</p><p><a href="{unsubscribe_url}">Unsubscribe</a></p>',
            'text_body' => "Hello {first_name}\nUnsubscribe: {unsubscribe_url}",
            'target_type' => 'all_contacts',
            'status' => 'draft',
        ];
    }
}
