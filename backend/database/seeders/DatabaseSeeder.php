<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperuserSeeder::class,
        ]);

        // To seed 12 demo clinical patients (clinically defensible synthetic data):
        // php artisan db:seed --class=ClinicalDemoSeeder
    }
}
