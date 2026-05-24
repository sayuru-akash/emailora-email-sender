<?php

namespace Database\Factories;

use App\Models\ContactImport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContactImport>
 */
class ContactImportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'file_name' => 'contacts.csv',
            'disk_path' => 'imports/test-contacts.csv',
            'file_type' => 'csv',
            'status' => 'uploaded',
            'duplicate_handling' => 'skip',
            'total_rows' => 0,
            'processed_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'duplicate_rows' => 0,
            'mapping' => ['email' => 'email'],
            'preview_rows' => null,
            'assigned_list_ids' => [],
            'assigned_tag_ids' => [],
            'uploaded_by' => User::factory(),
        ];
    }
}
