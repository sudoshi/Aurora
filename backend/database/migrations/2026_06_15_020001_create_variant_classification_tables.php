<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.variant_classifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('genomic_variant_id');
            $table->string('gene_symbol')->nullable();
            $table->string('computed_classification');
            $table->integer('computed_points')->default(0);
            $table->string('final_classification')->nullable();
            $table->string('status')->default('computed');
            $table->string('ruleset_version')->default('acmg-2015-svi-2020');
            $table->string('gene_specification_id')->nullable();
            $table->text('override_reason')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('genomic_variant_id')->references('id')->on('clinical.genomic_variants')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('app.users');
            $table->foreign('confirmed_by')->references('id')->on('app.users');
            $table->index('genomic_variant_id');
            $table->index('status');
        });

        Schema::create('clinical.classification_criteria', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('classification_id');
            $table->string('code');
            $table->string('applied_strength');
            $table->integer('points');
            $table->string('data_source');
            $table->string('evidence_value')->nullable();
            $table->text('rationale')->nullable();
            $table->string('set_by')->default('curator');
            $table->unsignedBigInteger('set_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('classification_id')->references('id')->on('clinical.variant_classifications')->onDelete('cascade');
            $table->foreign('set_by_user_id')->references('id')->on('app.users');
            $table->unique(['classification_id', 'code']);
            $table->index('classification_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.classification_criteria');
        Schema::dropIfExists('clinical.variant_classifications');
    }
};
