<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.imaging_response_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->cascadeOnDelete();
            $table->string('criteria_type', 50);
            $table->date('assessment_date');
            $table->string('body_site', 100)->nullable();
            $table->foreignId('baseline_study_id')->constrained('clinical.imaging_studies')->cascadeOnDelete();
            $table->foreignId('current_study_id')->constrained('clinical.imaging_studies')->cascadeOnDelete();
            $table->decimal('baseline_value', 18, 6)->nullable();
            $table->decimal('nadir_value', 18, 6)->nullable();
            $table->decimal('current_value', 18, 6)->nullable();
            $table->decimal('percent_change_from_baseline', 10, 4)->nullable();
            $table->decimal('percent_change_from_nadir', 10, 4)->nullable();
            $table->string('response_category', 10);
            $table->text('rationale')->nullable();
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->timestamps();

            $table->foreign('assessed_by')->references('id')->on('app.users')->nullOnDelete();
            $table->index(['patient_id', 'assessment_date']);
            $table->index(['patient_id', 'criteria_type']);
            $table->index(
                ['patient_id', 'criteria_type', 'baseline_study_id', 'current_study_id', 'source_type'],
                'imaging_response_source_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.imaging_response_assessments');
    }
};
