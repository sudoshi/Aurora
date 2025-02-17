<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Log::info('Creating patients table');

        Schema::create('dev.patients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Log::info('Patients table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping patients table');
        Schema::dropIfExists('dev.patients');
    }
};
