<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS dev');

        if (! Schema::hasTable('dev.patients')) {
            Schema::create('dev.patients', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('condition')->nullable();
                $table->string('status')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('dev.events')) {
            Schema::create('dev.events', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->timestamp('time');
                $table->unsignedInteger('duration')->nullable();
                $table->string('location')->nullable();
                $table->string('category')->nullable();
                $table->text('description')->nullable();
                $table->jsonb('team')->nullable();
                $table->jsonb('related_items')->nullable();
                $table->timestamps();

                $table->index('time');
                $table->index('category');
            });
        }

        if (! Schema::hasTable('dev.event_team_members')) {
            Schema::create('dev.event_team_members', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('user_id');
                $table->string('role')->nullable();
                $table->timestamps();

                $table->foreign('event_id')->references('id')->on('dev.events')->cascadeOnDelete();
                $table->unique(['event_id', 'user_id']);
            });
        }

        if (! Schema::hasTable('dev.event_patients')) {
            Schema::create('dev.event_patients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('event_id');
                $table->unsignedBigInteger('patient_id');
                $table->timestamps();

                $table->foreign('event_id')->references('id')->on('dev.events')->cascadeOnDelete();
                $table->foreign('patient_id')->references('id')->on('dev.patients')->cascadeOnDelete();
                $table->unique(['event_id', 'patient_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('dev.event_patients');
        Schema::dropIfExists('dev.event_team_members');
        Schema::dropIfExists('dev.events');
        Schema::dropIfExists('dev.patients');
    }
};
