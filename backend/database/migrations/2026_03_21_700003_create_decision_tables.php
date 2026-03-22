<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // app.decisions
        Schema::create('app.decisions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('proposed_by');
            $table->string('decision_type'); // treatment_recommendation, diagnostic_workup, referral, monitoring_plan, palliative, other
            $table->text('recommendation');
            $table->text('rationale')->nullable();
            $table->string('guideline_reference')->nullable();
            $table->string('status')->default('proposed'); // proposed, under_review, approved, rejected, deferred
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('finalized_by')->nullable();
            $table->string('urgency')->default('routine'); // routine, urgent, emergent
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('app.cases')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('app.clinical_sessions');
            $table->foreign('proposed_by')->references('id')->on('app.users');
            $table->foreign('finalized_by')->references('id')->on('app.users');

            $table->index('case_id');
            $table->index('session_id');
            $table->index('status');
            $table->index('urgency');
        });

        // app.decision_votes
        Schema::create('app.decision_votes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('decision_id');
            $table->unsignedBigInteger('user_id');
            $table->string('vote'); // agree, disagree, abstain
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('decision_id')->references('id')->on('app.decisions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('app.users')->onDelete('cascade');

            $table->unique(['decision_id', 'user_id']);
        });

        // app.follow_ups
        Schema::create('app.follow_ups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('decision_id');
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending'); // pending, in_progress, completed, cancelled
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('decision_id')->references('id')->on('app.decisions')->onDelete('cascade');
            $table->foreign('assigned_to')->references('id')->on('app.users');

            $table->index('decision_id');
            $table->index('status');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.follow_ups');
        Schema::dropIfExists('app.decision_votes');
        Schema::dropIfExists('app.decisions');
    }
};
