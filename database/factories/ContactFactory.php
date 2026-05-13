<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'full_name' => fake()->name(),
            'email' => $email,
            'email_normalized' => strtolower($email),
            'company' => fake()->company(),
            'country' => fake()->country(),
            'city' => fake()->city(),
            'source' => fake()->randomElement(['manual', 'import', 'website']),
            'status' => 'active',
            'consent_status' => 'opted_in',
        ];
    }
}
