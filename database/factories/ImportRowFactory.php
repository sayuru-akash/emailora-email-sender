<?php

namespace Database\Factories;

use App\Models\ContactImport;
use App\Models\ImportRow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRow>
 */
class ImportRowFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_import_id' => ContactImport::factory(),
            'row_number' => fake()->unique()->numberBetween(2, 2000),
            'status' => 'created',
            'raw_data' => ['email' => fake()->safeEmail()],
            'mapped_data' => ['email' => fake()->safeEmail()],
            'email_normalized' => fake()->safeEmail(),
            'error_message' => null,
            'contact_id' => null,
        ];
    }
}
