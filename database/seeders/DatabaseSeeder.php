<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Database\Seeders\EventSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test user if it doesn't exist
        User::firstOrCreate(
            ['email' => 'testuser@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        \App\Models\Patient::factory()->create(['id' => 1]);
        \App\Models\Patient::factory()->create(['id' => 5]);
        \App\Models\Patient::factory()->create(['id' => 6]);


        // Run event seeder
        $this->call([
            EventSeeder::class
        ]);
    }
}
