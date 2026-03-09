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
        Log::info('Creating event_team_members table');

        Schema::create('dev.event_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('dev.events')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('dev.users')->onDelete('cascade');
        });

        Log::info('Event_team_members table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping event_team_members table');
        Schema::dropIfExists('dev.event_team_members');
    }
};
