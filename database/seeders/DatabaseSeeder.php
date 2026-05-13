<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => env('OWNER_EMAIL', 'owner@example.com')],
            [
                'name' => env('OWNER_NAME', 'Owner'),
                'password' => Hash::make(env('OWNER_PASSWORD', 'password')),
                'role' => 'owner',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        collect([
            'company_name' => env('APP_NAME', 'Emailora'),
            'timezone' => env('APP_TIMEZONE', 'Asia/Colombo'),
            'default_provider' => env('EMAIL_PROVIDER', 'resend'),
            'default_from_email' => env('EMAIL_FROM_ADDRESS', 'no-reply@example.com'),
            'default_from_name' => env('EMAIL_FROM_NAME', 'Emailora'),
        ])->each(fn ($value, $key) => SystemSetting::query()->updateOrCreate(
            ['key' => $key],
            ['group' => str_starts_with($key, 'default_') ? 'email' : 'general', 'value' => $value]
        ));
    }
}
