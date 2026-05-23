<?php

namespace Database\Factories;

use App\Models\ListModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListModel>
 */
class ListModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => $name,
            'description' => fake()->sentence(),
            'status' => 'active',
            'color' => '#4f46e5',
        ];
    }
}
