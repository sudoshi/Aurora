<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clinical.clingen_gene_validity', function (Blueprint $table) {
            $table->id();
            $table->string('gene_symbol')->index();
            $table->string('disease_label');
            $table->string('disease_id')->nullable();        // MONDO ID
            $table->string('moi')->nullable();               // Mode of inheritance
            $table->string('classification');                // Current classification
            $table->string('baseline_classification')->nullable(); // Last-seen, for delta detection
            $table->timestamp('classification_date')->nullable();
            $table->string('report_url', 1024)->nullable();
            $table->timestamp('last_checked_at')->nullable();
            $table->timestamps();

            $table->unique(['gene_symbol', 'disease_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinical.clingen_gene_validity');
    }
};
