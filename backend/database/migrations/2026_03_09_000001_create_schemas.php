<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS app');
        DB::statement('CREATE SCHEMA IF NOT EXISTS clinical');
        DB::statement('SET search_path TO app, clinical, public');
    }

    public function down(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS clinical CASCADE');
        DB::statement('DROP SCHEMA IF EXISTS app CASCADE');
    }
};
