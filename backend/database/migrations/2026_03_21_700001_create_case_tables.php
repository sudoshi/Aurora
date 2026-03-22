<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // app.cases
        Schema::create('app.cases', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('specialty'); // oncology, surgical, rare_disease, complex_medical
            $table->string('urgency')->default('routine'); // routine, urgent, emergent
            $table->string('status')->default('draft'); // draft, active, in_review, closed, archived
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('case_type'); // tumor_board, surgical_review, rare_disease, medical_complex
            $table->text('clinical_question')->nullable();
            $table->text('summary')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('app.users')->onDelete('cascade');

            $table->index('status');
            $table->index('specialty');
            $table->index('urgency');
            $table->index('created_by');
            $table->index('patient_id');
            $table->index('scheduled_at');
        });

        // app.case_team_members
        Schema::create('app.case_team_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role'); // presenter, reviewer, observer, coordinator
            $table->timestamp('invited_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('app.cases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('app.users')->onDelete('cascade');
            $table->unique(['case_id', 'user_id']);
        });

        // app.case_annotations
        Schema::create('app.case_annotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('user_id');
            $table->string('domain'); // condition, medication, procedure, measurement, observation, imaging, genomic, general
            $table->string('record_ref')->nullable();
            $table->text('content');
            $table->jsonb('anchored_to')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('app.cases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('app.users');

            $table->index('case_id');
            $table->index('domain');
        });

        // app.case_documents
        Schema::create('app.case_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('uploaded_by');
            $table->string('filename');
            $table->string('filepath');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('document_type'); // pathology_report, radiology, genomic, clinical_note, external, other
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('case_id')->references('id')->on('app.cases')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('app.users');

            $table->index('case_id');
            $table->index('document_type');
        });

        // app.case_discussions
        Schema::create('app.case_discussions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->text('content');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('case_id')->references('id')->on('app.cases')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('app.users');
            $table->foreign('parent_id')->references('id')->on('app.case_discussions')->onDelete('cascade');

            $table->index('case_id');
            $table->index('parent_id');
        });

        // app.discussion_attachments
        Schema::create('app.discussion_attachments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('discussion_id');
            $table->string('filename');
            $table->string('filepath');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->foreign('discussion_id')->references('id')->on('app.case_discussions')->onDelete('cascade');

            $table->index('discussion_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app.discussion_attachments');
        Schema::dropIfExists('app.case_discussions');
        Schema::dropIfExists('app.case_documents');
        Schema::dropIfExists('app.case_annotations');
        Schema::dropIfExists('app.case_team_members');
        Schema::dropIfExists('app.cases');
    }
};
