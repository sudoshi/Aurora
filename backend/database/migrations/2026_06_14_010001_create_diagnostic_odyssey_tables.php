<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app.diagnostic_odysseys', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('case_id')->nullable();
            $table->string('title');
            $table->string('status')->default('referral'); // referral, phenotyping, testing, prioritization, mdt_review, matchmaking, diagnosed, reanalysis, closed
            $table->string('progress_status')->default('in_progress'); // Phenopackets: in_progress, solved, unsolved
            $table->text('referral_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('solved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('cascade');
            $table->foreign('case_id')->references('id')->on('app.cases')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('app.users');

            $table->index('patient_id');
            $table->index('status');
        });

        Schema::create('app.odyssey_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('odyssey_id');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->unsignedBigInteger('actor_id');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('odyssey_id')->references('id')->on('app.diagnostic_odysseys')->onDelete('cascade');
            $table->foreign('actor_id')->references('id')->on('app.users');

            $table->index('odyssey_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.odyssey_status_transitions');
        Schema::dropIfExists('app.diagnostic_odysseys');
    }
};
