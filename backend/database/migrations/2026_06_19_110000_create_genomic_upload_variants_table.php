<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clinical.genomic_uploads', function (Blueprint $table) {
            $table->timestamp('parsed_at')->nullable()->after('uploaded_by');
            $table->timestamp('matched_at')->nullable()->after('parsed_at');
            $table->timestamp('imported_at')->nullable()->after('matched_at');
            $table->timestamp('clinvar_annotated_at')->nullable()->after('imported_at');
            $table->text('error_message')->nullable()->after('clinvar_annotated_at');
            $table->jsonb('last_result')->nullable()->after('error_message');
        });

        Schema::create('clinical.genomic_upload_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('genomic_upload_id')->constrained('clinical.genomic_uploads')->cascadeOnDelete();
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('sample_id', 200)->nullable();
            $table->string('mapping_status', 50)->default('unmatched');
            $table->text('mapping_message')->nullable();
            $table->string('chromosome', 20);
            $table->bigInteger('position');
            $table->string('reference_allele', 500);
            $table->string('alternate_allele', 500);
            $table->string('genome_build', 20)->default('GRCh38');
            $table->string('gene_symbol', 100)->nullable();
            $table->string('variant', 500)->nullable();
            $table->string('variant_type', 50)->nullable();
            $table->string('zygosity', 30)->nullable();
            $table->decimal('allele_frequency', 8, 6)->nullable();
            $table->string('clinical_significance', 200)->nullable();
            $table->string('hgvs_c', 500)->nullable();
            $table->string('hgvs_p', 500)->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->string('variant_key', 64);
            $table->timestamps();

            $table->foreign('patient_id')->references('id')->on('clinical.patients')->nullOnDelete();
            $table->unique(['genomic_upload_id', 'variant_key'], 'genomic_upload_variants_upload_key_unique');
            $table->index(['genomic_upload_id', 'mapping_status'], 'genomic_upload_variants_upload_status_idx');
            $table->index(['patient_id', 'gene_symbol'], 'genomic_upload_variants_patient_gene_idx');
            $table->index(['chromosome', 'position'], 'genomic_upload_variants_coordinate_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.genomic_upload_variants');

        Schema::table('clinical.genomic_uploads', function (Blueprint $table) {
            $table->dropColumn([
                'parsed_at',
                'matched_at',
                'imported_at',
                'clinvar_annotated_at',
                'error_message',
                'last_result',
            ]);
        });
    }
};
