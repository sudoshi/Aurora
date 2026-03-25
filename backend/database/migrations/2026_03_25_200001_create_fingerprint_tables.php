<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure pgvector extension exists
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // 1. Patient fingerprints
        Schema::create('clinical.patient_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->unique();
            $table->boolean('genomic_available')->default(false);
            $table->boolean('volumetric_available')->default(false);
            $table->boolean('clinical_available')->default(false);
            $table->decimal('genomic_confidence', 5, 4)->nullable();
            $table->decimal('volumetric_confidence', 5, 4)->nullable();
            $table->decimal('clinical_confidence', 5, 4)->nullable();
            $table->string('encoder_version', 32)->default('v1.0');
            $table->timestamp('genomic_encoded_at')->nullable();
            $table->timestamp('volumetric_encoded_at')->nullable();
            $table->timestamp('clinical_encoded_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->cascadeOnDelete();
        });

        // Add pgvector columns (not supported by Blueprint)
        DB::statement('ALTER TABLE clinical.patient_fingerprints ADD COLUMN genomic_vector vector(256)');
        DB::statement('ALTER TABLE clinical.patient_fingerprints ADD COLUMN volumetric_vector vector(256)');
        DB::statement('ALTER TABLE clinical.patient_fingerprints ADD COLUMN clinical_vector vector(256)');

        // 2. Outcome trajectories
        Schema::create('clinical.outcome_trajectories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('patient_id')->unique();
            $table->decimal('tumor_response_score', 5, 4)->nullable();
            $table->decimal('treatment_tolerance_score', 5, 4)->nullable();
            $table->decimal('lab_trajectory_score', 5, 4)->nullable();
            $table->decimal('disease_stability_score', 5, 4)->nullable();
            $table->decimal('care_intensity_score', 5, 4)->nullable();
            $table->decimal('composite_score', 5, 4)->nullable();
            $table->string('clinician_rating', 20)->nullable();
            $table->text('clinician_factors')->nullable();
            $table->jsonb('decision_tags')->nullable();
            $table->text('hindsight_note')->nullable();
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->cascadeOnDelete();
            $table->foreign('assessed_by')->references('id')->on('app.users')->nullOnDelete();
        });

        // 3. Similarity search audit log
        Schema::create('clinical.similarity_searches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('query_patient_id');
            $table->unsignedBigInteger('searched_by');
            $table->jsonb('weights_used');
            $table->boolean('weights_customized')->default(false);
            $table->string('context', 20)->default('point_of_care');
            $table->jsonb('result_patient_ids');
            $table->jsonb('result_scores');
            $table->integer('result_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('query_patient_id')->references('id')->on('clinical.patients')->cascadeOnDelete();
            $table->foreign('searched_by')->references('id')->on('app.users')->cascadeOnDelete();
        });

        // 4. Fusion weight configurations
        Schema::create('clinical.fusion_weight_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('config_type', 20);
            $table->decimal('genomic_weight', 5, 4);
            $table->decimal('volumetric_weight', 5, 4);
            $table->decimal('clinical_weight', 5, 4);
            $table->jsonb('outcome_weights')->nullable();
            $table->boolean('is_active')->default(false);
            $table->integer('trained_on_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.similarity_searches');
        Schema::dropIfExists('clinical.outcome_trajectories');
        Schema::dropIfExists('clinical.patient_fingerprints');
        Schema::dropIfExists('clinical.fusion_weight_configs');
    }
};
