<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // app.sessions
        Schema::create('app.clinical_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_at');
            $table->integer('duration_minutes')->default(60);
            $table->string('status')->default('scheduled'); // scheduled, live, completed, cancelled
            $table->string('session_type'); // tumor_board, mdc, surgical_planning, grand_rounds, ad_hoc
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('app.users');

            $table->index('status');
            $table->index('session_type');
            $table->index('scheduled_at');
            $table->index('created_by');
        });

        // app.session_cases
        Schema::create('app.session_cases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('case_id');
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('presenter_id')->nullable();
            $table->integer('time_allotted_minutes')->default(15);
            $table->string('status')->default('pending'); // pending, presenting, discussed, skipped
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('app.clinical_sessions')->onDelete('cascade');
            $table->foreign('case_id')->references('id')->on('app.cases')->onDelete('cascade');
            $table->foreign('presenter_id')->references('id')->on('app.users');

            $table->unique(['session_id', 'case_id']);
        });

        // app.session_participants
        Schema::create('app.session_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('session_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role'); // moderator, presenter, reviewer, observer
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('session_id')->references('id')->on('app.clinical_sessions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('app.users')->onDelete('cascade');

            $table->unique(['session_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.session_participants');
        Schema::dropIfExists('app.session_cases');
        Schema::dropIfExists('app.clinical_sessions');
    }
};
