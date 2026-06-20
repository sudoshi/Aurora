<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.imaging_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_study_id')->constrained('clinical.imaging_studies')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('clinical.patients')->cascadeOnDelete();
            $table->string('feature_type', 50);
            $table->string('algorithm_name')->nullable();
            $table->string('feature_name');
            $table->text('feature_source_value')->nullable();
            $table->decimal('value_numeric', 18, 6)->nullable();
            $table->text('value_text')->nullable();
            $table->unsignedBigInteger('value_concept_id')->nullable();
            $table->string('unit', 50)->nullable();
            $table->string('body_site')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->boolean('requires_review')->default(false);
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->index('imaging_study_id');
            $table->index('patient_id');
            $table->index('feature_type');
            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.imaging_features');
    }
};
