<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.gene_drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('gene', 50)->index();
            $table->string('variant_pattern', 200)->default('*');
            $table->string('drug', 200);
            $table->string('drug_class', 100)->nullable();
            $table->string('relationship', 50);
            $table->string('evidence_level', 10);
            $table->text('indication')->nullable();
            $table->text('mechanism')->nullable();
            $table->string('source', 50)->default('manual');
            $table->text('source_url')->nullable();
            $table->timestamp('oncokb_last_synced_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamps();

            $table->unique(['gene', 'variant_pattern', 'drug'], 'gene_variant_drug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.gene_drug_interactions');
    }
};
