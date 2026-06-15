<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.variant_canonical_ids', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genomic_variant_id');
            $table->string('caid')->nullable();
            $table->string('vrs_id')->nullable();
            $table->string('clinvar_variation_id')->nullable();
            $table->string('dbsnp_rs')->nullable();
            $table->string('assembly')->default('GRCh38');
            $table->string('baseline_significance')->nullable();
            $table->string('baseline_review_status')->nullable();
            $table->timestamp('baselined_at')->nullable();
            $table->timestamp('canonicalized_at')->nullable();
            $table->timestamps();

            $table->foreign('genomic_variant_id')->references('id')->on('clinical.genomic_variants')->onDelete('cascade');
            $table->unique('genomic_variant_id');
            $table->index('clinvar_variation_id');
            $table->index('caid');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.variant_canonical_ids');
    }
};
