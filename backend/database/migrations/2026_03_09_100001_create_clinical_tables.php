<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure pgvector extension is available
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        // clinical.patients
        Schema::create('clinical.patients', function (Blueprint $table) {
            $table->id();
            $table->string('mrn')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('sex', 20)->nullable();
            $table->string('race', 100)->nullable();
            $table->string('ethnicity', 100)->nullable();
            $table->timestamp('deceased_at')->nullable();
            $table->unsignedBigInteger('institution_id')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('mrn');
            $table->index('last_name');
            $table->index('institution_id');
        });

        // clinical.patient_identifiers
        Schema::create('clinical.patient_identifiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('identifier_type');
            $table->string('identifier_value');
            $table->string('source_system')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index(['identifier_type', 'identifier_value']);
        });

        // clinical.conditions
        Schema::create('clinical.conditions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('concept_name');
            $table->string('concept_code')->nullable();
            $table->string('vocabulary', 50)->nullable(); // ICD10, SNOMED, custom
            $table->string('domain', 50)->nullable(); // oncology, surgical, rare_disease, complex_medical
            $table->string('status', 30)->default('active'); // active, resolved, chronic
            $table->date('onset_date')->nullable();
            $table->date('resolution_date')->nullable();
            $table->string('severity', 30)->nullable();
            $table->string('laterality', 30)->nullable();
            $table->string('body_site')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('concept_code');
            $table->index('domain');
            $table->index('status');
        });

        // clinical.medications
        Schema::create('clinical.medications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('drug_name');
            $table->string('concept_code')->nullable();
            $table->string('vocabulary', 50)->nullable();
            $table->string('route', 50)->nullable();
            $table->decimal('dose_value', 12, 4)->nullable();
            $table->string('dose_unit', 30)->nullable();
            $table->string('frequency', 50)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status', 30)->default('active'); // active, completed, discontinued
            $table->string('prescriber')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('concept_code');
            $table->index('status');
        });

        // clinical.procedures
        Schema::create('clinical.procedures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('procedure_name');
            $table->string('concept_code')->nullable();
            $table->string('vocabulary', 50)->nullable();
            $table->string('domain', 50)->nullable();
            $table->date('performed_date')->nullable();
            $table->string('performer')->nullable();
            $table->string('body_site')->nullable();
            $table->string('laterality', 30)->nullable();
            $table->text('notes')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('concept_code');
            $table->index('performed_date');
        });

        // clinical.measurements
        Schema::create('clinical.measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('measurement_name');
            $table->string('concept_code')->nullable();
            $table->string('vocabulary', 50)->nullable();
            $table->decimal('value_numeric', 18, 6)->nullable();
            $table->text('value_text')->nullable();
            $table->string('unit', 50)->nullable();
            $table->decimal('reference_range_low', 18, 6)->nullable();
            $table->decimal('reference_range_high', 18, 6)->nullable();
            $table->string('abnormal_flag', 10)->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('concept_code');
            $table->index('measured_at');
        });

        // clinical.observations
        Schema::create('clinical.observations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('observation_name');
            $table->string('concept_code')->nullable();
            $table->string('vocabulary', 50)->nullable();
            $table->text('value_text')->nullable();
            $table->decimal('value_numeric', 18, 6)->nullable();
            $table->timestamp('observed_at')->nullable();
            $table->string('category', 50)->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('concept_code');
            $table->index('observed_at');
        });

        // clinical.visits
        Schema::create('clinical.visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('visit_type', 30); // inpatient, outpatient, emergency, telehealth
            $table->string('facility')->nullable();
            $table->timestamp('admission_date')->nullable();
            $table->timestamp('discharge_date')->nullable();
            $table->string('attending_provider')->nullable();
            $table->string('department')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('admission_date');
        });

        // clinical.clinical_notes
        Schema::create('clinical.clinical_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->foreignId('visit_id')->nullable()->constrained('clinical.visits')->onDelete('set null');
            $table->string('note_type');
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('author')->nullable();
            $table->timestamp('authored_at')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('note_type');
            $table->index('authored_at');
        });

        // clinical.imaging_studies
        Schema::create('clinical.imaging_studies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('study_uid')->unique();
            $table->string('modality', 10); // CT, MRI, PET, US, XR, NM
            $table->date('study_date')->nullable();
            $table->string('description')->nullable();
            $table->string('body_part')->nullable();
            $table->string('laterality', 30)->nullable();
            $table->string('accession_number')->nullable();
            $table->integer('num_series')->nullable();
            $table->integer('num_instances')->nullable();
            $table->string('dicom_endpoint')->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('study_date');
            $table->index('modality');
        });

        // clinical.imaging_series
        Schema::create('clinical.imaging_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_study_id')->constrained('clinical.imaging_studies')->onDelete('cascade');
            $table->string('series_uid')->unique();
            $table->integer('series_number')->nullable();
            $table->string('modality', 10)->nullable();
            $table->string('description')->nullable();
            $table->integer('num_instances')->nullable();
            $table->timestamps();

            $table->index('imaging_study_id');
        });

        // clinical.imaging_instances
        Schema::create('clinical.imaging_instances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_series_id')->constrained('clinical.imaging_series')->onDelete('cascade');
            $table->string('sop_instance_uid')->unique();
            $table->integer('instance_number')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();

            $table->index('imaging_series_id');
        });

        // clinical.imaging_measurements
        Schema::create('clinical.imaging_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_study_id')->constrained('clinical.imaging_studies')->onDelete('cascade');
            $table->string('measurement_type', 30); // RECIST, volumetric, WHO
            $table->boolean('target_lesion')->default(false);
            $table->decimal('value_numeric', 18, 6);
            $table->string('unit', 30);
            $table->string('measured_by')->nullable();
            $table->timestamp('measured_at')->nullable();
            $table->timestamps();

            $table->index('imaging_study_id');
        });

        // clinical.imaging_segmentations
        Schema::create('clinical.imaging_segmentations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imaging_study_id')->constrained('clinical.imaging_studies')->onDelete('cascade');
            $table->string('segmentation_uid')->unique();
            $table->string('algorithm')->nullable();
            $table->string('label')->nullable();
            $table->decimal('volume_mm3', 18, 4)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('imaging_study_id');
        });

        // clinical.genomic_variants
        Schema::create('clinical.genomic_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('gene');
            $table->string('variant')->nullable(); // e.g. V600E
            $table->string('variant_type', 30)->nullable(); // SNV, indel, fusion, CNV, rearrangement
            $table->string('chromosome', 10)->nullable();
            $table->bigInteger('position')->nullable();
            $table->string('ref_allele')->nullable();
            $table->string('alt_allele')->nullable();
            $table->string('zygosity', 30)->nullable();
            $table->decimal('allele_frequency', 8, 6)->nullable();
            $table->string('clinical_significance', 30)->nullable(); // pathogenic, likely_pathogenic, VUS, likely_benign, benign
            $table->string('actionability', 30)->nullable();
            $table->string('source_id')->nullable();
            $table->string('source_type')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('gene');
            $table->index('clinical_significance');
        });

        // clinical.condition_eras
        Schema::create('clinical.condition_eras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('concept_name');
            $table->date('era_start');
            $table->date('era_end')->nullable();
            $table->integer('occurrence_count')->default(1);
            $table->timestamps();

            $table->index('patient_id');
        });

        // clinical.drug_eras
        Schema::create('clinical.drug_eras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('clinical.patients')->onDelete('cascade');
            $table->string('drug_name');
            $table->date('era_start');
            $table->date('era_end')->nullable();
            $table->integer('gap_days')->default(0);
            $table->timestamps();

            $table->index('patient_id');
        });

        // clinical.patient_embeddings
        Schema::create('clinical.patient_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained('clinical.patients')->onDelete('cascade');
            $table->string('model_version')->nullable();
            $table->timestamp('computed_at')->nullable();
            $table->timestamps();
        });

        // Add vector column using raw SQL (pgvector)
        DB::statement('ALTER TABLE clinical.patient_embeddings ADD COLUMN embedding vector(768)');
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.patient_embeddings');
        Schema::dropIfExists('clinical.drug_eras');
        Schema::dropIfExists('clinical.condition_eras');
        Schema::dropIfExists('clinical.genomic_variants');
        Schema::dropIfExists('clinical.imaging_segmentations');
        Schema::dropIfExists('clinical.imaging_measurements');
        Schema::dropIfExists('clinical.imaging_instances');
        Schema::dropIfExists('clinical.imaging_series');
        Schema::dropIfExists('clinical.imaging_studies');
        Schema::dropIfExists('clinical.clinical_notes');
        Schema::dropIfExists('clinical.visits');
        Schema::dropIfExists('clinical.observations');
        Schema::dropIfExists('clinical.measurements');
        Schema::dropIfExists('clinical.procedures');
        Schema::dropIfExists('clinical.medications');
        Schema::dropIfExists('clinical.conditions');
        Schema::dropIfExists('clinical.patient_identifiers');
        Schema::dropIfExists('clinical.patients');
    }
};
