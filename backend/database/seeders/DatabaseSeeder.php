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
        // Create team members
        User::firstOrCreate(
            ['email' => 'lisa.anderson@example.com'],
            [
                'name' => 'Dr. Lisa Anderson',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'david.kim@example.com'],
            [
                'name' => 'Dr. David Kim',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        User::firstOrCreate(
            ['email' => 'rachel.green@example.com'],
            [
                'name' => 'Dr. Rachel Green',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );


        // Run seeders
        $this->call([
            PatientSeeder::class,
            EventSeeder::class
        ]);
    }
}
