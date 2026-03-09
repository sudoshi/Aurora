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
        Log::info('Creating events table');

        Schema::create('dev.events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamp('time');
            $table->integer('duration');
            $table->string('location');
            $table->string('category');
            $table->text('description')->nullable();
            $table->json('team');
            $table->json('related_items');
            $table->timestamps();
        });

        Log::info('Events table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping events table');
        Schema::dropIfExists('dev.events');
    }
};
