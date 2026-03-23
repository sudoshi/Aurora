<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.clinvar_variants', function (Blueprint $table) {
            $table->id();
            $table->string('variation_id', 30)->nullable();
            $table->string('rs_id', 30)->nullable();

            // Coordinates
            $table->string('chromosome', 10);
            $table->bigInteger('position');
            $table->string('reference_allele', 500);
            $table->string('alternate_allele', 500);
            $table->string('genome_build', 20)->default('GRCh38');

            // Annotation
            $table->string('gene_symbol', 100)->nullable();
            $table->string('hgvs', 500)->nullable();
            $table->string('clinical_significance', 200)->nullable();
            $table->text('disease_name')->nullable();
            $table->string('review_status', 200)->nullable();
            $table->boolean('is_pathogenic')->default(false);

            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['chromosome', 'position', 'reference_allele', 'alternate_allele', 'genome_build'],
                'clinvar_coords_unique'
            );
            $table->index('gene_symbol');
            $table->index('clinical_significance');
            $table->index('is_pathogenic');
        });

        Schema::create('clinical.clinvar_sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('genome_build', 20)->default('GRCh38');
            $table->boolean('papu_only')->default(false);
            $table->string('source_url')->nullable();
            $table->enum('status', ['running', 'completed', 'failed'])->default('running');
            $table->integer('variants_inserted')->default(0);
            $table->integer('variants_updated')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.clinvar_sync_log');
        Schema::dropIfExists('clinical.clinvar_variants');
    }
};
