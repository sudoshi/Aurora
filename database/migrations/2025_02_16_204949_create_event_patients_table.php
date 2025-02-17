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
        Log::info('Creating event_patients table');

        Schema::create('dev.event_patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('patient_id');
            $table->timestamps();

            $table->foreign('event_id')->references('id')->on('dev.events')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('dev.patients')->onDelete('cascade');
        });

        Log::info('Event_patients table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping event_patients table');
        Schema::dropIfExists('dev.event_patients');
    }
};
