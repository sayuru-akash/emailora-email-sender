<?php

namespace Database\Factories;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(['contacts', 'campaigns', 'imports', 'auth']),
            'event' => fake()->randomElement(['contact.created', 'campaign.queued', 'import.completed']),
            'severity' => 'info',
            'user_id' => User::factory(),
            'user_name' => fake()->name(),
            'user_email' => fake()->safeEmail(),
            'subject_type' => null,
            'subject_id' => null,
            'subject_name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'properties' => ['count' => fake()->numberBetween(1, 10)],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'method' => 'GET',
            'url' => '/activity-logs',
            'route_name' => 'activity-logs.index',
            'occurred_at' => now(),
        ];
    }
}
