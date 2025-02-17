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
        Log::info('Creating case_team_members table');

        Schema::create('dev.case_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('dev.cases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Log::info('Case_team_members table created successfully.');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Log::info('Dropping case_team_members table');
        Schema::dropIfExists('dev.case_team_members');
    }
};
