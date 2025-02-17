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
        Log::info('Creating cases table');

        Schema::create('dev.cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->string('title');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('dev.patients')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });

        Log::info('Cases table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping cases table');
        Schema::dropIfExists('dev.cases');
    }
};
